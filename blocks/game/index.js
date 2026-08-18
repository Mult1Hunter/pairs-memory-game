/* Block editor UI for the Pairs - Memory Game block. Plain JS on purpose
   (no build step): the block is a thin wrapper that renders the same
   markup as the shortcode through ServerSideRender. */
(function (wp) {
  var el = wp.element.createElement;
  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var TextControl = wp.components.TextControl;
  var ServerSideRender = wp.serverSideRender;

  registerBlockType('pairs-memory-game/game', {
    edit: function (props) {
      var atts = props.attributes;
      var blockProps = useBlockProps();
      return el(
        'div',
        blockProps,
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: __('Game options', 'pairs-memory-game'), initialOpen: true },
            el(SelectControl, {
              label: __('Preselected difficulty', 'pairs-memory-game'),
              value: atts.tier,
              options: [
                { label: __('Default from settings', 'pairs-memory-game'), value: '' },
                { label: __('Easy', 'pairs-memory-game'), value: 'easy' },
                { label: __('Medium', 'pairs-memory-game'), value: 'medium' },
                { label: __('Hard', 'pairs-memory-game'), value: 'hard' }
              ],
              onChange: function (v) { props.setAttributes({ tier: v }); }
            }),
            el(TextControl, {
              label: __('Intro title override', 'pairs-memory-game'),
              value: atts.title,
              onChange: function (v) { props.setAttributes({ title: v }); }
            })
          )
        ),
        el(ServerSideRender, { block: 'pairs-memory-game/game', attributes: atts })
      );
    },
    save: function () { return null; }
  });
})(window.wp);
