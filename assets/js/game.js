/* Pairs - Memory Game: frontend.
 *
 * All configuration and every user-facing string arrives in
 * window.PairsMGConfig (wp_localize_script) - there is no hardcoded copy
 * here, so translating the plugin never touches this file.
 *
 * The score this file computes is only a preview for the count-up on the
 * win screen. The server derives elapsed time from its own clock via the
 * run token and recomputes the score, so a tampered client can only lie
 * to itself.
 */
(function () {
  "use strict";

  var CFG = window.PairsMGConfig || {};
  var T = CFG.i18n || {};
  var API = CFG.restUrl || "";
  var TIER_ORDER = ["easy", "medium", "hard"];
  var TIER_LABEL = CFG.tierLabels || { easy: "Easy", medium: "Medium", hard: "Hard" };
  var PAIR_COUNTS = CFG.pairCounts || { easy: 6, medium: 10, hard: 14 };
  var LB = !!CFG.leaderboard;
  var PAR = Number(CFG.parSecondsPerPair) || 3.2;

  var state = {
    tier: CFG.defaultTier || "medium",
    cards: [],
    flippedUids: [],
    moves: 0,
    matchedPairs: 0,
    totalPairs: 0,
    timerId: null,
    elapsedSec: 0,
    lastResult: null,
    lockBoard: false,
    pending: null,
    gameActive: false,
    sessionToken: null,
    runToken: null,
    pool: {}
  };
  var lbActiveTier = state.tier;
  var root = null;

  /* ---------------- helpers ---------------- */
  function $(id) { return root.querySelector('[data-pmg="' + id + '"]'); }
  function all(sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
  function on(id, evt, fn) { var el = $(id); if (el) el.addEventListener(evt, fn); }
  function setText(id, text) { var el = $(id); if (el) el.textContent = text; }
  function fmt(str, n) { return String(str || "").replace("%d", String(n)); }
  function plural(forms, n) {
    if (!forms || typeof forms === "string") return fmt(forms, n);
    var mod = n % 100;
    var i = mod === 1 ? 0 : mod === 2 ? 1 : (mod === 3 || mod === 4) ? 2 : 3;
    return fmt(forms[Math.min(i, forms.length - 1)], n);
  }

  function shuffle(arr) {
    var a = arr.slice();
    for (var i = a.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var t = a[i]; a[i] = a[j]; a[j] = t;
    }
    return a;
  }
  function fmtTime(sec) {
    var m = Math.floor(sec / 60);
    var s = sec % 60;
    return (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
  }
  var prefersReducedMotion = window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------------- REST ---------------- */
  function api(path, opts) {
    opts = opts || {};
    var init = {
      method: opts.method || "GET",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin"
    };
    if (opts.body) init.body = JSON.stringify(opts.body);
    return fetch(API + path, init).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok || !data || data.ok === false) {
          var err = new Error((data && (data.message || data.error)) || ("HTTP " + res.status));
          err.code = data && data.error;
          err.status = res.status;
          throw err;
        }
        return data;
      });
    });
  }

  /* ---------------- Sound ---------------- */
  var Sound = (function () {
    var ctx = null;
    var enabled = CFG.soundDefault !== false;
    try {
      var saved = localStorage.getItem("pairsmgSound");
      if (saved !== null) enabled = saved === "1";
    } catch (e) { /* storage unavailable */ }

    function ensureCtx() {
      if (!ctx) {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        ctx = new AC();
      }
      if (ctx.state === "suspended") ctx.resume();
      return ctx;
    }
    function tone(freq, startOffset, dur, opts) {
      opts = opts || {};
      if (!enabled) return;
      var c = ensureCtx();
      if (!c) return;
      var t0 = c.currentTime + (startOffset || 0);
      var osc = c.createOscillator();
      var gain = c.createGain();
      osc.type = opts.type || "sine";
      osc.frequency.setValueAtTime(freq, t0);
      if (opts.glideTo) osc.frequency.exponentialRampToValueAtTime(Math.max(opts.glideTo, 1), t0 + dur);
      var peak = opts.gain != null ? opts.gain : 0.16;
      gain.gain.setValueAtTime(0, t0);
      gain.gain.linearRampToValueAtTime(peak, t0 + Math.min(0.015, dur / 3));
      gain.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
      osc.connect(gain).connect(c.destination);
      osc.start(t0);
      osc.stop(t0 + dur + 0.02);
    }
    function noiseBurst(startOffset, dur, opts) {
      opts = opts || {};
      if (!enabled) return;
      var c = ensureCtx();
      if (!c) return;
      var t0 = c.currentTime + (startOffset || 0);
      var frames = Math.max(1, Math.floor(c.sampleRate * dur));
      var buffer = c.createBuffer(1, frames, c.sampleRate);
      var data = buffer.getChannelData(0);
      for (var i = 0; i < frames; i++) data[i] = Math.random() * 2 - 1;
      var src = c.createBufferSource();
      src.buffer = buffer;
      var filter = c.createBiquadFilter();
      filter.type = opts.filterType || "bandpass";
      filter.frequency.setValueAtTime(opts.filterFreq || 1200, t0);
      if (opts.filterFreqEnd) filter.frequency.exponentialRampToValueAtTime(opts.filterFreqEnd, t0 + dur);
      filter.Q.value = opts.q != null ? opts.q : 1;
      var gain = c.createGain();
      var peak = opts.gain != null ? opts.gain : 0.2;
      gain.gain.setValueAtTime(0, t0);
      gain.gain.linearRampToValueAtTime(peak, t0 + Math.min(0.006, dur / 4));
      gain.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
      src.connect(filter).connect(gain).connect(c.destination);
      src.start(t0);
      src.stop(t0 + dur + 0.02);
    }
    return {
      isEnabled: function () { return enabled; },
      setEnabled: function (v) {
        enabled = v;
        try { localStorage.setItem("pairsmgSound", v ? "1" : "0"); } catch (e) { /* ignore */ }
      },
      unlock: function () { ensureCtx(); },
      flip: function () { noiseBurst(0, 0.05, { filterType: "highpass", filterFreq: 2600, gain: 0.05 }); },
      match: function () {
        tone(150, 0, 0.12, { type: "sine", gain: 0.18, glideTo: 65 });
        noiseBurst(0, 0.09, { filterType: "bandpass", filterFreq: 1500, filterFreqEnd: 550, q: 1.2, gain: 0.22 });
      },
      mismatch: function () {
        tone(100, 0, 0.14, { type: "triangle", gain: 0.08, glideTo: 55 });
        noiseBurst(0, 0.1, { filterType: "lowpass", filterFreq: 500, gain: 0.08 });
      },
      verified: function () {
        noiseBurst(0, 0.03, { filterType: "highpass", filterFreq: 4000, gain: 0.1 });
        tone(1200, 0, 0.04, { type: "sine", gain: 0.08 });
      },
      win: function () {
        tone(700, 0, 0.12, { type: "sawtooth", gain: 0.12, glideTo: 180 });
        noiseBurst(0.07, 0.16, { filterType: "bandpass", filterFreq: 3000, filterFreqEnd: 1000, gain: 0.12 });
        tone(150, 0.28, 0.16, { type: "sine", gain: 0.22, glideTo: 60 });
        noiseBurst(0.28, 0.1, { filterType: "bandpass", filterFreq: 1500, filterFreqEnd: 500, gain: 0.24 });
        [523.25, 659.25, 784.0, 1046.5].forEach(function (f, i) {
          tone(f, 0.42 + i * 0.09, 0.18, { type: "triangle", gain: 0.15 });
        });
      }
    };
  })();

  /* ---------------- Presentation ---------------- */
  function gridColumnsFor(totalCards) {
    var w = window.innerWidth;
    var table;
    if (w < 480) table = { 12: 3, 20: 4, 28: 5 };
    else if (w < 768) table = { 12: 4, 20: 5, 28: 6 };
    else if (w < 1100) table = { 12: 4, 20: 5, 28: 7 };
    else table = { 12: 6, 20: 7, 28: 7 };
    if (table[totalCards]) return table[totalCards];
    var cols = Math.ceil(Math.sqrt(totalCards * 1.3));
    var max = w < 480 ? 5 : w < 768 ? 6 : 8;
    return Math.max(3, Math.min(max, cols));
  }

  function animateScoreCountUp(target) {
    var el = $("winScore");
    if (prefersReducedMotion || !window.requestAnimationFrame) {
      el.textContent = String(target);
      return;
    }
    var duration = 700;
    var start = null;
    function step(ts) {
      if (start === null) start = ts;
      var p = Math.min(1, (ts - start) / duration);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = String(Math.round(target * eased));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  function launchConfetti(intensity) {
    if (prefersReducedMotion || CFG.confetti === false) return;
    var count = intensity === "big" ? 60 : intensity === "medium" ? 30 : 0;
    if (!count) return;
    var layer = $("confettiLayer");
    if (!layer) return;
    var cs = getComputedStyle(root);
    var colors = [cs.getPropertyValue("--pmg-accent"), cs.getPropertyValue("--pmg-success"),
      cs.getPropertyValue("--pmg-card-frame"), cs.getPropertyValue("--pmg-danger"), cs.getPropertyValue("--pmg-bg")]
      .map(function (c) { return c.trim() || "#999"; });
    for (var i = 0; i < count; i++) {
      var el = document.createElement("div");
      el.className = "pmg-confetti-piece";
      var size = 6 + Math.random() * 6;
      el.style.left = (Math.random() * 100) + "%";
      el.style.background = colors[i % colors.length];
      el.style.width = size + "px";
      el.style.height = (size * 0.4) + "px";
      var delay = Math.random() * 0.4;
      var duration = 1.6 + Math.random() * 1.1;
      el.style.animationDelay = delay + "s";
      el.style.animationDuration = duration + "s";
      el.style.setProperty("--drift", Math.round((Math.random() * 2 - 1) * 60) + "px");
      el.style.setProperty("--rot", Math.round(Math.random() * 360) + "deg");
      layer.appendChild(el);
      (function (node, lifeMs) {
        setTimeout(function () { node.remove(); }, lifeMs);
      })(el, (delay + duration) * 1000 + 400);
    }
  }

  function showScreen(name) {
    all(".pmg-screen").forEach(function (s) { s.classList.remove("pmg-active"); });
    var el = root.querySelector('[data-screen="' + name + '"]');
    if (el) el.classList.add("pmg-active");
    var tags = { gate: T.tagGate, setup: T.tagSetup, game: T.tagGame, win: T.tagWin, leaderboard: T.tagLeaderboard };
    setText("screenTag", tags[name] || "");
  }

  /* ---------------- Gate / bot protection ---------------- */
  function setGateStatus(text) { setText("gateStatus", text || ""); }

  function onVerified(data) {
    state.sessionToken = data.sessionToken;
    setGateStatus("");
    setGameControlsEnabled(true);
    return loadPool().then(function () {
      buildTierGrid();
      refreshMiniLeaderboard();
      showScreen("setup");
    });
  }

  function verifyWith(token) {
    setGateStatus(T.verifying);
    return api("/verify", { method: "POST", body: { captchaToken: token || "" } })
      .then(function (data) {
        if (token) Sound.verified();
        return onVerified(data);
      })
      .catch(function (err) {
        setGateStatus(err.message || T.verifyFailed);
        resetWidget();
      });
  }

  var widgetId = null;
  function resetWidget() {
    try {
      if (CFG.captchaProvider === "turnstile" && window.turnstile) window.turnstile.reset(widgetId);
      else if (CFG.captchaProvider === "recaptcha_v2" && window.grecaptcha) window.grecaptcha.reset(widgetId);
      else if (CFG.captchaProvider === "hcaptcha" && window.hcaptcha) window.hcaptcha.reset(widgetId);
    } catch (e) { /* ignore */ }
  }

  function initCaptcha() {
    var provider = CFG.captchaProvider || "none";
    var holder = $("captchaWidget");
    var siteKey = CFG.captchaSiteKey || "";

    if (provider === "none") {
      verifyWith("");
      return;
    }

    if (provider === "recaptcha_v3") {
      setGateStatus(T.verifying);
      poll(function () {
        if (!window.grecaptcha || !window.grecaptcha.ready) return false;
        window.grecaptcha.ready(function () {
          window.grecaptcha.execute(siteKey, { action: "pairsmg_start" })
            .then(verifyWith)
            .catch(function () { setGateStatus(T.verifyFailed); });
        });
        return true;
      });
      return;
    }

    if (!holder || !siteKey) return;
    var params = { sitekey: siteKey, callback: verifyWith, theme: "light" };
    params["error-callback"] = function () { setGateStatus(T.verifyFailed); };

    poll(function () {
      if (provider === "turnstile" && window.turnstile) {
        widgetId = window.turnstile.render(holder, params); return true;
      }
      if (provider === "recaptcha_v2" && window.grecaptcha && window.grecaptcha.render) {
        widgetId = window.grecaptcha.render(holder, params); return true;
      }
      if (provider === "hcaptcha" && window.hcaptcha) {
        widgetId = window.hcaptcha.render(holder, params); return true;
      }
      return false;
    });
  }

  // Third-party scripts load async; poll briefly rather than depend on order.
  function poll(fn) {
    if (fn()) return;
    var tries = 0;
    var iv = setInterval(function () {
      if (fn() || ++tries > 60) clearInterval(iv);
    }, 150);
  }

  /* ---------------- Pool / config ---------------- */
  function loadPool() {
    return api("/config").then(function (data) {
      state.pool = data.pool || {};
      if (data.pairCounts) PAIR_COUNTS = data.pairCounts;
    }).catch(function () { /* fall back to localized counts */ });
  }

  /* ---------------- Setup screen ---------------- */
  function buildTierGrid() {
    var grid = $("tierGrid");
    if (!grid) return;
    grid.innerHTML = "";
    TIER_ORDER.forEach(function (key) {
      var pairs = PAIR_COUNTS[key];
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "pmg-tier-btn" + (key === state.tier ? " pmg-selected" : "");
      btn.setAttribute("data-tier", key);
      btn.innerHTML =
        '<span class="pmg-tier-name"></span>' +
        '<span class="pmg-tier-pairs"></span>' +
        '<span class="pmg-tier-sub"></span>' +
        '<span class="pmg-tier-best"></span>';
      btn.querySelector(".pmg-tier-name").textContent = TIER_LABEL[key];
      btn.querySelector(".pmg-tier-pairs").textContent = fmt(T.pairsCount, pairs);
      btn.querySelector(".pmg-tier-sub").textContent = fmt(T.cardsOnBoard, pairs * 2);
      btn.addEventListener("click", function () {
        state.tier = key;
        grid.querySelectorAll(".pmg-tier-btn").forEach(function (b) { b.classList.remove("pmg-selected"); });
        btn.classList.add("pmg-selected");
        refreshMiniLeaderboard();
      });
      grid.appendChild(btn);
    });
    refreshTierBests();
  }

  function refreshTierBests() {
    if (!LB) return;
    TIER_ORDER.forEach(function (key) {
      api("/leaderboard?tier=" + encodeURIComponent(key) + "&limit=1")
        .then(function (data) {
          var el = root.querySelector('.pmg-tier-btn[data-tier="' + key + '"] .pmg-tier-best');
          if (!el) return;
          var top = (data.entries || [])[0];
          el.textContent = top ? fmt(T.best, top.score) : "";
        })
        .catch(function () { /* decoration only */ });
    });
  }

  /* ---------------- Game ---------------- */
  function startGame() {
    if (!state.sessionToken) {
      showScreen("gate");
      setGateStatus(T.verifyFirst);
      return;
    }
    api("/start-run", { method: "POST", body: { sessionToken: state.sessionToken, tier: state.tier } })
      .then(function (data) {
        state.runToken = data.runToken;
        beginBoard(data.pairs, data.deck || []);
      })
      .catch(function (err) {
        if (err.status === 401) {
          state.sessionToken = null;
          setGameControlsEnabled(false);
          showScreen("gate");
          setGateStatus(T.sessionExpired);
          resetWidget();
          if (CFG.captchaProvider === "none" || CFG.captchaProvider === "recaptcha_v3") initCaptcha();
        } else if (err.code === "not_enough_cards") {
          window.alert(T.notEnoughCards);
        }
      });
  }

  function beginBoard(pairs, serverDeck) {
    state.totalPairs = pairs;
    var chosen = (serverDeck || []).slice(0, pairs);
    var deck = [];
    chosen.forEach(function (item, idx) {
      deck.push({ uid: "a" + idx, poolId: item.id, item: item, flipped: false, matched: false });
      deck.push({ uid: "b" + idx, poolId: item.id, item: item, flipped: false, matched: false });
    });
    state.cards = shuffle(deck);
    discardPending();
    state.flippedUids = [];
    state.moves = 0;
    state.matchedPairs = 0;
    state.elapsedSec = 0;
    state.lockBoard = false;
    state.gameActive = true;

    setText("gTier", TIER_LABEL[state.tier] + " - " + fmt(T.pairsCount, pairs));
    setText("gMoves", "0");
    setText("gPairs", "0 / " + pairs);
    setText("gTime", "00:00");

    renderBoard();
    showScreen("game");
    startTimer();
  }

  function renderBoard() {
    var board = $("board");
    board.innerHTML = "";
    var total = state.cards.length;
    board.style.gridTemplateColumns = "repeat(" + gridColumnsFor(total) + ", 1fr)";
    var dealStep = prefersReducedMotion ? 0 : Math.max(18, Math.min(45, 900 / total));

    state.cards.forEach(function (card, idx) {
      var wrap = document.createElement("div");
      wrap.className = "pmg-mem-card";
      wrap.setAttribute("data-uid", card.uid);
      wrap.style.setProperty("--deal-delay", (idx * dealStep) + "ms");

      var btn = document.createElement("button");
      btn.className = "pmg-mem-card-btn";
      btn.type = "button";
      btn.setAttribute("aria-label", T.cardLabel || "Card");

      var inner = document.createElement("div");
      inner.className = "pmg-mem-card-inner";

      var back = document.createElement("div");
      back.className = "pmg-mem-face pmg-back";

      var front = document.createElement("div");
      front.className = "pmg-mem-face pmg-front";
      var img = document.createElement("img");
      img.className = "pmg-mem-img" + (card.item.fit === "full" ? " pmg-mem-img-full" : "");
      img.src = card.item.url;
      img.alt = "";
      img.loading = "eager";
      img.decoding = "async";
      front.appendChild(img);

      inner.appendChild(back);
      inner.appendChild(front);
      btn.appendChild(inner);
      wrap.appendChild(btn);
      board.appendChild(wrap);

      btn.addEventListener("click", function () { onCardClick(card.uid); });
    });
  }

  /* A flipped pair is "pending" while its outcome animates. The next click
     settles it immediately, so a fast player never waits on an animation. */
  function settlePending(early) {
    if (!state.pending) return;
    clearTimeout(state.pending.timer);
    var fn = state.pending.resolve;
    state.pending = null;
    fn(!!early);
  }
  function discardPending() {
    if (!state.pending) return;
    clearTimeout(state.pending.timer);
    state.pending = null;
  }

  function cardEl(uid) { return root.querySelector('.pmg-mem-card[data-uid="' + uid + '"]'); }

  function onCardClick(uid) {
    if (state.lockBoard) return;
    var card = state.cards.find(function (c) { return c.uid === uid; });
    if (!card || card.matched) return;
    if (card.flipped) { settlePending(true); return; }
    settlePending(true);
    if (state.flippedUids.length >= 2) return;

    card.flipped = true;
    state.flippedUids.push(uid);
    cardEl(uid).classList.add("pmg-flipped");
    Sound.flip();

    if (state.flippedUids.length !== 2) return;

    state.moves += 1;
    setText("gMoves", String(state.moves));
    var c1 = state.cards.find(function (c) { return c.uid === state.flippedUids[0]; });
    var c2 = state.cards.find(function (c) { return c.uid === state.flippedUids[1]; });
    var el1 = cardEl(c1.uid);
    var el2 = cardEl(c2.uid);
    state.flippedUids = [];

    var resolve;
    var delay;
    if (c1.poolId === c2.poolId) {
      delay = 380;
      resolve = function () {
        Sound.match();
        c1.matched = true; c2.matched = true;
        el1.classList.add("pmg-matched");
        el2.classList.add("pmg-matched");
        state.matchedPairs += 1;
        setText("gPairs", state.matchedPairs + " / " + state.totalPairs);
        if (state.matchedPairs === state.totalPairs) finishGame();
      };
    } else {
      Sound.mismatch();
      el1.classList.add("pmg-mismatch"); el2.classList.add("pmg-mismatch");
      delay = 900;
      resolve = function (early) {
        c1.flipped = false; c2.flipped = false;
        if (early) {
          el1.classList.add("pmg-quick"); el2.classList.add("pmg-quick");
          setTimeout(function () {
            el1.classList.remove("pmg-quick"); el2.classList.remove("pmg-quick");
          }, 220);
        }
        el1.classList.remove("pmg-flipped", "pmg-mismatch");
        el2.classList.remove("pmg-flipped", "pmg-mismatch");
      };
    }
    state.pending = { resolve: resolve, timer: setTimeout(function () { settlePending(false); }, delay) };
  }

  function startTimer() {
    if (state.timerId) clearInterval(state.timerId);
    state.timerId = setInterval(function () {
      state.elapsedSec += 1;
      setText("gTime", fmtTime(state.elapsedSec));
    }, 1000);
  }
  function stopTimer() {
    if (state.timerId) clearInterval(state.timerId);
    state.timerId = null;
  }

  function previewScore(pairs, moves, elapsed) {
    var parTime = pairs * PAR;
    var effMoves = Math.max(moves, pairs);
    var timeFactor = Math.min(1, parTime / Math.max(elapsed, 1));
    var moveFactor = pairs / effMoves;
    return Math.max(25, Math.round(1000 * moveFactor * timeFactor));
  }

  function finishGame() {
    stopTimer();
    state.gameActive = false;
    var preview = previewScore(state.totalPairs, state.moves, state.elapsedSec);

    function present(score, timeSeconds) {
      state.lastResult = { score: score, timeSeconds: timeSeconds, moves: state.moves, pairs: state.totalPairs, tier: state.tier };
      var celebration = score >= 850 ? "big" : score >= 600 ? "medium" : "none";
      Sound.win();
      animateScoreCountUp(score);
      $("winScore").classList.toggle("pmg-score-great", celebration === "big");
      setText("winTime", fmtTime(timeSeconds));
      setText("winMoves", String(state.moves));
      setText("winTier", TIER_LABEL[state.tier] + " - " + fmt(T.pairsCount, state.totalPairs));
      var nameInput = $("nameInput");
      if (nameInput) nameInput.value = "";
      var saved = $("savedNote");
      if (saved) saved.classList.remove("pmg-show");
      var saveBtn = $("saveScoreBtn");
      if (saveBtn) saveBtn.disabled = false;
      setText("submitError", "");
      setTimeout(function () {
        showScreen("win");
        if (celebration !== "none") launchConfetti(celebration);
      }, 400);
    }

    if (!state.runToken) { present(preview, state.elapsedSec); return; }

    api("/finish-run", { method: "POST", body: { runToken: state.runToken, moves: state.moves } })
      .then(function (data) { present(data.score, data.timeSeconds); })
      .catch(function () { present(preview, state.elapsedSec); });
  }

  /* ---------------- Leaderboards ---------------- */
  function refreshMiniLeaderboard() {
    if (!LB) return;
    var tier = state.tier;
    setText("miniBoardTier", "- " + TIER_LABEL[tier]);
    var host = $("miniBoard");
    if (!host) return;
    host.innerHTML = '<div class="pmg-mini-empty"></div>';
    host.firstChild.textContent = T.loading;

    api("/leaderboard?tier=" + encodeURIComponent(tier) + "&limit=5")
      .then(function (data) {
        var list = data.entries || [];
        host.innerHTML = "";
        if (!list.length) {
          var empty = document.createElement("div");
          empty.className = "pmg-mini-empty";
          empty.textContent = T.noScoresYet;
          host.appendChild(empty);
          return;
        }
        list.forEach(function (entry, i) {
          var row = document.createElement("div");
          row.className = "pmg-mini-row";
          row.innerHTML = '<span class="pmg-mini-rank"></span><span class="pmg-mini-name"></span><span class="pmg-mini-score"></span>';
          row.querySelector(".pmg-mini-rank").textContent = String(i + 1);
          row.querySelector(".pmg-mini-name").textContent = entry.name;
          row.querySelector(".pmg-mini-score").textContent = entry.score;
          host.appendChild(row);
        });
      })
      .catch(function () {
        host.innerHTML = '<div class="pmg-mini-empty"></div>';
        host.firstChild.textContent = T.lbUnavailable;
      });
  }

  function buildLbTabs() {
    var host = $("lbTabs");
    if (!host) return;
    host.innerHTML = "";
    TIER_ORDER.forEach(function (key) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "pmg-lb-tab" + (key === lbActiveTier ? " pmg-selected" : "");
      btn.setAttribute("role", "tab");
      btn.setAttribute("data-tier", key);
      btn.textContent = TIER_LABEL[key];
      btn.addEventListener("click", function () { lbActiveTier = key; renderFullLeaderboard(); });
      host.appendChild(btn);
    });
  }

  function renderFullLeaderboard() {
    buildLbTabs();
    var host = $("lbList");
    if (!host) return;
    host.innerHTML = '<div class="pmg-lb-empty"></div>';
    host.firstChild.textContent = T.loading;
    var limit = CFG.leaderboardLimit || 50;

    api("/leaderboard?tier=" + encodeURIComponent(lbActiveTier) + "&limit=" + limit)
      .then(function (data) {
        var list = data.entries || [];
        host.innerHTML = "";
        setText("lbCount", plural(T.resultsCount, list.length));
        if (!list.length) {
          var empty = document.createElement("div");
          empty.className = "pmg-lb-empty";
          empty.textContent = T.noScoresYet;
          host.appendChild(empty);
          return;
        }
        list.forEach(function (entry, i) {
          var row = document.createElement("div");
          row.className = "pmg-lb-row" + (i === 0 ? " pmg-top1" : i === 1 ? " pmg-top2" : i === 2 ? " pmg-top3" : "");
          row.innerHTML =
            '<span class="pmg-lb-rank"></span>' +
            '<span><span class="pmg-lb-name"></span><br/><span class="pmg-lb-meta"></span></span>' +
            '<span class="pmg-lb-score"></span>' +
            '<span></span>';
          row.querySelector(".pmg-lb-rank").textContent = String(i + 1);
          row.querySelector(".pmg-lb-name").textContent = entry.name;
          row.querySelector(".pmg-lb-meta").textContent = fmtTime(entry.time_seconds) + " - " + fmt(T.movesCount, entry.moves);
          row.querySelector(".pmg-lb-score").textContent = entry.score;
          host.appendChild(row);
        });
      })
      .catch(function () {
        host.innerHTML = '<div class="pmg-lb-empty"></div>';
        host.firstChild.textContent = T.lbUnavailable;
      });
  }

  /* ---------------- Wiring ---------------- */
  function wire() {
    on("startBtn", "click", startGame);
    function toLb() { lbActiveTier = state.tier; renderFullLeaderboard(); showScreen("leaderboard"); }
    on("toLeaderboardBtn", "click", toLb);
    on("toLeaderboardBtn2", "click", toLb);
    on("backFromLbBtn", "click", function () { refreshMiniLeaderboard(); showScreen("setup"); });
    on("backToSetupBtn", "click", function () { refreshMiniLeaderboard(); showScreen("setup"); });

    on("quitBtn", "click", function () {
      stopTimer();
      discardPending();
      state.gameActive = false;
      refreshMiniLeaderboard();
      showScreen("setup");
    });
    on("restartBtn", "click", function () { stopTimer(); startGame(); });
    on("playAgainBtn", "click", function () { startGame(); });

    on("nameForm", "submit", function (e) {
      e.preventDefault();
      if (!state.lastResult || !state.runToken) return;
      var btn = $("saveScoreBtn");
      btn.disabled = true;
      setText("submitError", "");
      api("/submit-score", { method: "POST", body: { runToken: state.runToken, name: $("nameInput").value } })
        .then(function () {
          state.runToken = null;
          $("savedNote").classList.add("pmg-show");
        })
        .catch(function (err) {
          btn.disabled = false;
          var msg = T.saveFailed;
          if (err.code === "already_submitted") msg = T.alreadySaved;
          else if (err.code === "rate_limited") msg = T.rateLimited;
          else if (err.status === 401) msg = T.sessionLost;
          setText("submitError", msg);
        });
    });

    var soundBtn = $("soundToggle");
    function updateSoundIcon() {
      soundBtn.classList.toggle("pmg-muted", !Sound.isEnabled());
      soundBtn.setAttribute("title", Sound.isEnabled() ? (T.soundOn || "") : (T.soundOff || ""));
    }
    if (soundBtn) {
      soundBtn.addEventListener("click", function () {
        Sound.unlock();
        Sound.setEnabled(!Sound.isEnabled());
        updateSoundIcon();
      });
      updateSoundIcon();
    }

    root.addEventListener("pointerdown", function () { Sound.unlock(); }, { once: true });

    window.addEventListener("resize", function () {
      var gameScreen = root.querySelector('[data-screen="game"]');
      if (!gameScreen || !gameScreen.classList.contains("pmg-active")) return;
      $("board").style.gridTemplateColumns = "repeat(" + gridColumnsFor(state.cards.length) + ", 1fr)";
    });

    window.addEventListener("beforeunload", function (e) {
      if (state.gameActive) { e.preventDefault(); e.returnValue = ""; }
    });
  }

  /* ---------------- Immersive / fullscreen (phones) ---------------- */
  var IMMERSIVE_MAX_WIDTH = 900;
  var immersiveOn = false;

  function isSmallScreen() { return window.innerWidth <= IMMERSIVE_MAX_WIDTH; }
  function enterImmersive() {
    if (root.classList.contains("pmg-is-immersive")) return;
    root.classList.add("pmg-is-immersive");
    document.body.classList.add("pairsmg-immersive-lock");
  }
  function exitImmersive() {
    root.classList.remove("pmg-is-immersive");
    document.body.classList.remove("pairsmg-immersive-lock");
    if (document.fullscreenElement && document.exitFullscreen) {
      document.exitFullscreen().catch(function () {});
    }
  }
  function tryNativeFullscreen() {
    if (!isSmallScreen() || document.fullscreenElement) return;
    var el = document.documentElement;
    var req = el.requestFullscreen || el.webkitRequestFullscreen;
    if (!req) return;
    try {
      var p = req.call(el);
      if (p && p.catch) p.catch(function () {});
    } catch (e) { /* denied */ }
  }
  function initImmersive() {
    immersiveOn = root.getAttribute("data-pmg-immersive") === "1" && CFG.immersive !== false;
    if (!immersiveOn) return;
    if (isSmallScreen()) enterImmersive();
    root.addEventListener("pointerdown", function () { tryNativeFullscreen(); }, { once: true });
    on("exitBtn", "click", function () {
      var home = CFG.exitUrl || "/";
      if (state.gameActive && !window.confirm(T.leaveConfirm)) return;
      state.gameActive = false;
      exitImmersive();
      window.location.href = home;
    });
    document.addEventListener("fullscreenchange", function () {
      if (!document.fullscreenElement && !isSmallScreen()) exitImmersive();
    });
    window.addEventListener("resize", function () {
      if (!isSmallScreen()) exitImmersive(); else enterImmersive();
    });
  }

  function setGameControlsEnabled(enabled) {
    ["startBtn", "toLeaderboardBtn", "toLeaderboardBtn2"].forEach(function (k) {
      var el = $(k);
      if (el) el.disabled = !enabled;
    });
  }

  function init() {
    root = document.querySelector(".pairsmg-app");
    if (!root) return;
    var instanceTier = root.getAttribute("data-pmg-tier");
    if (instanceTier && TIER_ORDER.indexOf(instanceTier) !== -1) {
      state.tier = instanceTier;
      lbActiveTier = instanceTier;
    }
    wire();
    setGameControlsEnabled(false);
    initImmersive();
    showScreen("gate");
    initCaptcha();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
