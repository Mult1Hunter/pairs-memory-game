# -*- coding: utf-8 -*-
"""Builds languages/pairs-memory-game-sl_SI.po (run from anywhere) from the .pot plus the
translation table below. Run after regenerating the .pot; then compile with
`wp i18n make-mo languages`. Untranslated msgids are left empty (falls back
to English) and listed on stdout so they can be added here.
"""
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
POT = os.path.join(HERE, "..", "languages", "pairs-memory-game.pot")
PO = os.path.join(HERE, "..", "languages", "pairs-memory-game-sl_SI.po")

T = {
    "Pairs - Memory Game": "Pari - Igra spomina",
    "A memory (concentration) game with your own card images, three difficulty tiers, server-verified scores, per-tier leaderboards and optional bot protection (Turnstile, reCAPTCHA, hCaptcha).": "Igra spomina z lastnimi slikami kartic, tremi težavnostmi, strežniško preverjenimi rezultati, ločenimi lestvicami in izbirno zaščito pred boti (Turnstile, reCAPTCHA, hCaptcha).",
    "You do not have permission to access this page.": "Nimate dovoljenja za dostop do te strani.",
    "Memory Game - Leaderboards": "Igra spomina - lestvice",
    "Score deleted.": "Rezultat je izbrisan.",
    "Deleted %d scores.": "Izbrisanih rezultatov: %d.",
    "Export this tier as CSV": "Izvozi to težavnost kot CSV",
    "No scores for this difficulty yet.": "Za to težavnost še ni rezultatov.",
    "Rank": "Mesto",
    "Name": "Ime",
    "Score": "Rezultat",
    "Time": "Čas",
    "Moves": "Poteze",
    "Date": "Datum",
    "Action": "Dejanje",
    "Delete this score?": "Izbrišem ta rezultat?",
    "Delete": "Izbriši",
    "Delete ALL scores for this difficulty? This cannot be undone.": "Izbrišem VSE rezultate za to težavnost? Tega ni mogoče razveljaviti.",
    "Clear this leaderboard": "Počisti to lestvico",
    "You do not have permission to do that.": "Za to nimate dovoljenja.",
    "Security check failed.": "Varnostno preverjanje ni uspelo.",
    "General": "Splošno",
    "Game": "Igra",
    "Bot protection": "Zaščita pred boti",
    "Appearance": "Videz",
    "Advanced": "Napredno",
    "Memory Game": "Igra spomina",
    "Settings": "Nastavitve",
    "Leaderboards": "Lestvice",
    "Choose card back image": "Izberi sliko za hrbtno stran kartic",
    "Use this image": "Uporabi to sliko",
    "Where the game is": "Kje je igra",
    "(page missing or unpublished - save settings to recreate it)": "(stran manjka ali ni objavljena - shranite nastavitve, da jo znova ustvarite)",
    "no dedicated page (turn it on under General)": "brez namenske strani (vklopite jo pod Splošno)",
    "Shortcode:": "Kratka koda:",
    'Block: \\"Pairs - Memory Game\\"': 'Blok: \\"Pari - Igra spomina\\"',
    "Published cards: %1$d (%2$d special). The hardest board needs %3$d.": "Objavljenih kartic: %1$d (od tega %2$d posebnih). Najtežja plošča jih potrebuje %3$d.",
    "Manage cards": "Upravljaj kartice",
    "Until then the built-in deck (%d cards) tops up the board.": "Do takrat ploščo dopolni vgrajeni komplet (%d kartic).",
    "The built-in deck is off, so some difficulties cannot start.": "Vgrajeni komplet je izklopljen, zato nekaterih težavnosti ni mogoče zagnati.",
    "Brand line": "Naslovna vrstica",
    "Small header text above the game. Defaults to the site title.": "Kratko besedilo nad igro. Privzeto naslov spletnega mesta.",
    "Intro eyebrow": "Nadnaslov uvoda",
    "Short label above the intro title. Leave empty for the default.": "Kratka oznaka nad uvodnim naslovom. Prazno pomeni privzeto.",
    "Intro title": "Uvodni naslov",
    "Intro text": "Uvodno besedilo",
    "Leaderboard title": "Naslov lestvice",
    "Max name length": "Največja dolžina imena",
    "Characters allowed in the player name on the leaderboard.": "Dovoljeno število znakov v imenu igralca na lestvici.",
    "Name when left blank": "Ime, če ostane prazno",
    "Dedicated page": "Namenska stran",
    "Create and maintain a page for the game at the address below": "Ustvari in vzdržuj stran z igro na spodnjem naslovu",
    "The plugin keeps a page at this slug and renames it when the slug changes. It never renames a page it did not create. Turn this off if you only use the shortcode or block.": "Vtičnik vzdržuje stran na tem naslovu in jo preimenuje, če naslov spremenite. Nikoli ne preimenuje strani, ki je ni ustvaril sam. Izklopite, če uporabljate samo kratko kodo ali blok.",
    "Page slug": "Naslov strani",
    "Exit URL": "Povratni naslov",
    'Where the \\"back to site\\" button (shown on phones in full-screen mode) leads. Empty means the home page.': 'Kam vodi gumb \\"Na stran\\" (prikazan na telefonih v celozaslonskem načinu). Prazno pomeni domačo stran.',
    "Pairs - Easy": "Parov - Lahko",
    "Pairs - Medium": "Parov - Srednje",
    "Pairs - Hard": "Parov - Težko",
    "Each difficulty has its own leaderboard. Changing a pair count keeps old scores in the same tier, so change it before the game goes live if you can.": "Vsaka težavnost ima svojo lestvico. Če spremenite število parov, stari rezultati ostanejo v isti težavnosti - po možnosti to spremenite, preden gre igra v živo.",
    "Preselected difficulty": "Vnaprej izbrana težavnost",
    "Special cards per board": "Posebnih kartic na ploščo",
    'How many cards flagged \\"special\\" are guaranteed on every board (if that many exist). 0 = no guarantee, they appear at random like the rest.': 'Koliko kartic, označenih kot \\"posebne\\", je zagotovljenih na vsaki plošči (če jih je toliko). 0 = brez zagotovila, pojavijo se naključno kot ostale.',
    "Built-in deck": "Vgrajeni komplet",
    "Top up the board with the built-in animal cards when there are not enough of my own": "Dopolni ploščo z vgrajenimi živalskimi karticami, kadar mojih ni dovolj",
    "Once you have published enough cards for the hardest board, the built-in ones stop appearing by themselves.": "Ko objavite dovolj kartic za najtežjo ploščo, se vgrajene nehajo pojavljati same od sebe.",
    "Leaderboard": "Lestvica",
    "Let players save their score with a name": "Igralcem dovoli, da rezultat shranijo z imenom",
    "Off: the game still shows the score, but nothing is stored.": "Izklopljeno: igra rezultat še vedno pokaže, a ničesar ne shrani.",
    "Leaderboard entries shown": "Prikazanih vnosov na lestvici",
    "Sound": "Zvok",
    "Sound effects on by default (players can toggle)": "Zvočni učinki privzeto vklopljeni (igralci jih lahko izklopijo)",
    "Confetti": "Konfeti",
    "Confetti on a good result": "Konfeti ob dobrem rezultatu",
    "Full screen on phones": "Celozaslonski način na telefonih",
    "Pin the game over the whole viewport on small screens": "Na majhnih zaslonih igro razpni čez celoten zaslon",
    "Recommended for a game that is played at events from a QR code. Off: the game sits inside the page like any other block.": "Priporočeno za igro, ki se igra na dogodkih prek QR kode. Izklopljeno: igra je del strani kot kateri koli drug blok.",
    "Provider": "Ponudnik",
    "Players solve one challenge per visit before the first game. Score submissions are always tied to a server-issued single-use token, so bot protection guards against automated play, not against score tampering (which is prevented regardless).": "Igralci pred prvo igro enkrat na obisk rešijo preizkus. Oddaja rezultata je vedno vezana na strežniški enkratni žeton, zato zaščita pred boti preprečuje avtomatizirano igranje, ne ponarejanja rezultatov (to je preprečeno v vsakem primeru).",
    "Site key": "Javni ključ (site key)",
    "The public key rendered in the browser.": "Javni ključ, ki se uporabi v brskalniku.",
    "Secret key": "Skrivni ključ",
    "A secret key is stored. Leave blank to keep it.": "Skrivni ključ je shranjen. Pustite prazno, da ga obdržite.",
    "Never leaves the server.": "Nikoli ne zapusti strežnika.",
    "Test mode": "Testni način",
    "Use the provider's official always-pass test keys": "Uporabi uradne testne ključe ponudnika, ki vedno uspejo",
    "For local and staging sites, where real keys will not validate. The server-side verification still runs. reCAPTCHA v3 has no test keys - in test mode it is skipped. Turn this off before going live.": "Za lokalna in testna okolja, kjer pravi ključi ne delujejo. Strežniško preverjanje se še vedno izvede. reCAPTCHA v3 nima testnih ključev - v testnem načinu se preskoči. Pred objavo v živo izklopite.",
    "reCAPTCHA v3 score threshold": "Prag ocene reCAPTCHA v3",
    "Scores below this are rejected. Google suggests 0.5.": "Ocene pod tem pragom so zavrnjene. Google priporoča 0.5.",
    "Privacy": "Zasebnost",
    'With a provider selected, the game page loads that provider\'s script and the verification token is sent to the provider\'s servers along with the visitor\'s IP address. Mention this in your privacy policy. With \\"None\\", no third-party requests are made.': 'Ko je ponudnik izbran, stran z igro naloži njegovo skripto, žeton preverjanja pa se skupaj z IP naslovom obiskovalca pošlje na strežnike ponudnika. To omenite v politiki zasebnosti. Pri \\"Brez\\" ni nobenih zunanjih zahtev.',
    "Theme": "Tema",
    "Parchment (warm)": "Pergament (toplo)",
    "Light": "Svetlo",
    "Dark": "Temno",
    "Custom colours": "Barve po meri",
    'Presets fill the colour fields; pick \\"Custom\\" to keep your own values.': 'Prednastavitve izpolnijo barvna polja; izberite \\"Po meri\\", da obdržite svoje vrednosti.',
    "Background": "Ozadje",
    "Panels": "Plošče",
    "Text": "Besedilo",
    "Accent / buttons": "Poudarek / gumbi",
    "Success (matched cards)": "Uspeh (najdeni pari)",
    "Card back": "Hrbtna stran kartice",
    "Card frame": "Okvir kartice",
    "Fonts": "Pisave",
    "Bundled (Rajdhani + Open Sans, served from this site)": "Priložene (Rajdhani + Open Sans, strežene s te strani)",
    "Inherit from the theme": "Podeduj iz teme",
    "Card shape": "Oblika kartice",
    "Portrait 7:10 (playing card)": "Pokončna 7:10 (igralna karta)",
    "Portrait 3:4": "Pokončna 3:4",
    "Square": "Kvadratna",
    "Corner radius (px)": "Zaobljenost robov (px)",
    "0 for square corners everywhere.": "0 za oglate robove povsod.",
    "Card image fit": "Prileganje slike na kartici",
    "Inset - image centred with a margin (logos)": "Vstavljeno - slika na sredini z robom (logotipi)",
    "Full face - image fills the card (finished artwork)": "Cela kartica - slika zapolni kartico (končna grafika)",
    "Default for your cards; each card can override it.": "Privzeto za vaše kartice; vsaka kartica lahko to spremeni.",
    "Card back image": "Slika na hrbtni strani",
    "Choose image": "Izberi sliko",
    "Remove": "Odstrani",
    "Optional. Drawn centred on the card back on top of the card back colour. A logo or emblem, ideally SVG or PNG with transparency.": "Izbirno. Narisana na sredini hrbtne strani čez barvo hrbta. Logotip ali znak, najbolje SVG ali PNG s prosojnostjo.",
    "Verifications per IP per hour": "Preverjanj na IP na uro",
    "Each verification may cost an outbound request to the bot-protection provider. 0 disables the limit.": "Vsako preverjanje lahko pomeni zunanjo zahtevo do ponudnika zaščite. 0 izklopi omejitev.",
    "Game starts per IP per hour": "Začetkov igre na IP na uro",
    "Scores saved per IP per hour": "Shranjenih rezultatov na IP na uro",
    "Reverse proxy / CDN": "Posredniški strežnik / CDN",
    "This site is behind Cloudflare or another proxy - read the visitor IP from CF-Connecting-IP / X-Forwarded-For": "Ta stran je za Cloudflarom ali drugim posrednikom - IP obiskovalca beri iz CF-Connecting-IP / X-Forwarded-For",
    "Only turn this on if the site really is behind a proxy, otherwise a visitor could forge the header and dodge the rate limits. IPs are only ever stored as a salted hash.": "Vklopite samo, če je stran res za posrednikom, sicer bi obiskovalec lahko ponaredil glavo in se izognil omejitvam. IP naslovi so vedno shranjeni le kot soljen izvleček.",
    "Uninstall": "Odstranitev",
    "Delete all plugin data (settings, scores, cards) when the plugin is deleted": "Ob izbrisu vtičnika izbriši vse njegove podatke (nastavitve, rezultate, kartice)",
    "Verification": "Preverjanje",
    "Setup": "Priprava",
    "Result": "Rezultat",
    "Verifying...": "Preverjanje ...",
    "Verification failed. Please refresh the page and try again.": "Preverjanje ni uspelo. Osvežite stran in poskusite znova.",
    "Please complete the verification first.": "Najprej dokončajte preverjanje.",
    "Your session has expired. Please verify again.": "Seja je potekla. Prosimo, ponovite preverjanje.",
    "%d pairs": "%d parov",
    "%d cards on the board": "%d kartic na plošči",
    "Best: %d": "Najboljši: %d",
    "Loading...": "Nalaganje ...",
    "No scores for this difficulty yet - be the first.": "Za to težavnost še ni rezultatov - bodi prvi.",
    "The leaderboard cannot be loaded right now.": "Lestvice trenutno ni mogoče naložiti.",
    "%d moves": "%d potez",
    "The score could not be saved.": "Rezultata ni bilo mogoče shraniti.",
    "This score has already been saved.": "Ta rezultat je že shranjen.",
    "Too many attempts. Please try again in a few minutes.": "Preveč poskusov. Poskusite čez nekaj minut.",
    "The session has expired, the score could not be saved.": "Seja je potekla, rezultata ni bilo mogoče shraniti.",
    "There are not enough cards for this board size yet.": "Za to velikost plošče še ni dovolj kartic.",
    "The game is not finished. Leave anyway?": "Igra še ni končana. Res želite oditi?",
    "Memory card": "Kartica igre spomina",
    "Sound on": "Zvok vklopljen",
    "Sound off": "Zvok izklopljen",
    "None (no challenge)": "Brez (brez preizkusa)",
    "Cloudflare Turnstile": "Cloudflare Turnstile",
    "Google reCAPTCHA v2 (checkbox)": "Google reCAPTCHA v2 (potrditveno polje)",
    "Google reCAPTCHA v3 (invisible)": "Google reCAPTCHA v3 (nevidna)",
    "hCaptcha": "hCaptcha",
    "Missing verification token.": "Manjka žeton preverjanja.",
    "Bot protection is not configured. Enter the keys in the plugin settings.": "Zaščita pred boti ni nastavljena. Vnesite ključe v nastavitvah vtičnika.",
    "Verification failed. Please try again.": "Preverjanje ni uspelo. Poskusite znova.",
    "Cards": "Kartice",
    "Card": "Kartica",
    "Add card": "Dodaj kartico",
    "Add new card": "Dodaj novo kartico",
    "Edit card": "Uredi kartico",
    "New card": "Nova kartica",
    "View card": "Poglej kartico",
    "Search cards": "Išči kartice",
    "No cards yet.": "Še ni kartic.",
    "No cards in the trash.": "V smeteh ni kartic.",
    "Card image (front face)": "Slika kartice (sprednja stran)",
    "Set card image": "Nastavi sliko kartice",
    "Remove card image": "Odstrani sliko kartice",
    "Use as card image": "Uporabi kot sliko kartice",
    "Card settings": "Nastavitve kartice",
    "Special card (guaranteed on every board, up to the quota in Settings)": "Posebna kartica (zagotovljena na vsaki plošči, do kvote v Nastavitvah)",
    "Image fit": "Prileganje slike",
    "Use the global setting": "Uporabi splošno nastavitev",
    "Inset (logo with margin)": "Vstavljeno (logotip z robom)",
    "Full face (edge to edge)": "Cela kartica (od roba do roba)",
    "Set the featured image below - that is the picture shown when the card is turned. Published cards are in the game; drafts are not.": "Spodaj nastavite glavno sliko - to je slika, ki se prikaže, ko igralec kartico obrne. Objavljene kartice so v igri, osnutki ne.",
    "Image guidelines": "Priporočila za sliko",
    "Square (1:1), about 600 x 600 px, or the card ratio chosen in Appearance for full-face images": "Kvadratna (1:1), približno 600 x 600 px, ali v razmerju kartice iz zavihka Videz za slike čez celo kartico",
    "PNG with transparent background for logos, JPG/WebP for photos": "PNG s prozornim ozadjem za logotipe, JPG/WebP za fotografije",
    "Under 200 KB": "Manj kot 200 KB",
    "No fine print - a card is only about 80 px wide on a phone": "Brez drobnega besedila - kartica je na telefonu široka le okoli 80 px",
    "Image": "Slika",
    "Special": "Posebna",
    "missing": "manjka",
    "yes": "da",
    "Unknown difficulty.": "Neznana težavnost.",
    "Requests from other sites are not allowed.": "Zahteve z drugih spletnih mest niso dovoljene.",
    "This run has already been finished.": "Ta igra je že končana.",
    "The leaderboard is turned off.": "Lestvica je izklopljena.",
    "Easy": "Lahko",
    "Medium": "Srednje",
    "Hard": "Težko",
    "Memory challenge": "Izziv spomina",
    "Find the pairs": "Poišči pare",
    "Turn two cards at a time and find every matching pair. Fewer moves and a faster time mean a higher score. See if you can make it onto the leaderboard.": "Obrni po dve kartici in poišči vse pare. Manj potez in krajši čas pomenita višji rezultat. Preveri, ali se uvrstiš na lestvico.",
    "Anonymous player": "Anonimni igralec",
    'Pairs - Memory Game: bot protection is enabled but the keys are missing. Enter them in Memory Game > Settings, or set the provider to \\"None\\". Only administrators see this notice.': 'Pari - Igra spomina: zaščita pred boti je vklopljena, a ključi manjkajo. Vnesite jih v Igra spomina > Nastavitve ali nastavite ponudnika na \\"Brez\\". To obvestilo vidijo samo skrbniki.',
    "Invalid token.": "Neveljaven žeton.",
    "Invalid token signature.": "Neveljaven podpis žetona.",
    "Invalid token payload.": "Neveljavna vsebina žetona.",
    "Token has expired.": "Žeton je potekel.",
    "Back to site": "Na stran",
    "Toggle sound": "Preklopi zvok",
    "One quick check": "Hiter preizkus",
    "A short verification keeps the leaderboard for real players. It only takes a moment.": "Kratko preverjanje ohranja lestvico za prave igralce. Traja le trenutek.",
    "Choose difficulty": "Izberi težavnost",
    "Start game": "Začni igro",
    "View leaderboard": "Ogled lestvice",
    "Top scores": "Najboljši rezultati",
    "Show all": "Prikaži vse",
    "Difficulty": "Težavnost",
    "Pairs": "Pari",
    "Quit": "Prekini",
    "Restart": "Ponovno začni",
    "All pairs found!": "Vsi pari najdeni!",
    "score": "rezultat",
    "How is the score calculated?": "Kako se izračuna rezultat?",
    "The score combines speed and accuracy: fewer wrong guesses and a shorter time mean a higher score (1000 at most). Each difficulty has its own leaderboard, so scores are always compared between boards of the same size.": "Rezultat združuje hitrost in natančnost: manj napačnih poskusov in krajši čas pomenita višji rezultat (največ 1000). Vsaka težavnost ima svojo lestvico, zato se rezultati vedno primerjajo med enako velikimi ploščami.",
    "Board": "Plošča",
    "Your name for the leaderboard": "Tvoje ime za lestvico",
    "e.g. Alex": "npr. Janez",
    "Up to %d characters. The name is shown publicly on the leaderboard.": "Največ %d znakov. Ime bo javno prikazano na lestvici.",
    "Save to leaderboard": "Dodaj na lestvico",
    "Play again": "Igraj znova",
    "Saved to the leaderboard": "Shranjeno na lestvico",
    "Change difficulty": "Spremeni težavnost",
    "Back": "Nazaj",
    "Choose leaderboard difficulty": "Izberi težavnost lestvice",
    "Each difficulty has its own leaderboard, so scores are always compared between boards of the same size. Within a difficulty the score combines speed and accuracy.": "Vsaka težavnost ima svojo lestvico, zato se rezultati vedno primerjajo med enako velikimi ploščami. Znotraj težavnosti rezultat združuje hitrost in natančnost.",
    "Game options": "Možnosti igre",
    "Default from settings": "Privzeto iz nastavitev",
    "Intro title override": "Nadomestni uvodni naslov",
    "The memory game with leaderboards. One per page.": "Igra spomina z lestvicami. Ena na stran.",
    "memory": "spomin",
    "game": "igra",
    "pairs": "pari",
    "concentration": "koncentracija",
    "leaderboard": "lestvica",
}

PLURALS = {
    "%d result": ["%d rezultat", "%d rezultata", "%d rezultati", "%d rezultatov"],
}

HEADER = '''msgid ""
msgstr ""
"Project-Id-Version: Pairs - Memory Game\\n"
"Report-Msgid-Bugs-To: https://github.com/Mult1Hunter/pairs-memory-game/issues\\n"
"Language: sl_SI\\n"
"Language-Team: Slovenian\\n"
"Last-Translator: Matic Korošec\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=4; plural=(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3);\\n"
"X-Domain: pairs-memory-game\\n"

'''


def main():
    pot = open(POT, encoding="utf-8").read()
    # Split into entries on blank lines; skip the header entry.
    blocks = pot.split("\n\n")
    out = [HEADER]
    missing = []
    for block in blocks[1:]:
        m = re.search(r'^msgid "(.*)"$', block, re.M)
        if not m:
            continue
        msgid = m.group(1)
        if msgid == "":
            continue
        pm = re.search(r'^msgid_plural "(.*)"$', block, re.M)
        comments = "\n".join(l for l in block.splitlines() if l.startswith("#"))
        entry = (comments + "\n") if comments else ""
        if pm:
            forms = PLURALS.get(msgid)
            entry += 'msgid "%s"\nmsgid_plural "%s"\n' % (msgid, pm.group(1))
            if forms:
                for i, f in enumerate(forms):
                    entry += 'msgstr[%d] "%s"\n' % (i, f)
            else:
                missing.append(msgid)
                for i in range(4):
                    entry += 'msgstr[%d] ""\n' % i
        else:
            tr = T.get(msgid)
            if tr is None:
                # URLs and names stay as they are.
                if msgid.startswith("http") or msgid == "Matic Korošec":
                    tr = msgid
                else:
                    missing.append(msgid)
                    tr = ""
            entry += 'msgid "%s"\nmsgstr "%s"\n' % (msgid, tr)
        out.append(entry)
    open(PO, "w", encoding="utf-8").write("\n".join(out))
    print("wrote", PO, "-", len(missing), "untranslated")
    for m in missing:
        print("  ", m)


if __name__ == "__main__":
    main()
