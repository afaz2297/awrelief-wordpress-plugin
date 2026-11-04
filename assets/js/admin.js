/* AWRELIEF dashboard: media pickers and the WebP conversion tool */
jQuery(function ($) {
  $('[data-afz-media]').each(function () {
    const box = $(this), input = box.find('input'), preview = box.find('.afz-media__preview');
    let frame;
    box.on('click', '[data-afz-pick]', function (e) {
      e.preventDefault();
      frame = frame || wp.media({ title: 'Choose image', library: { type: 'image' }, multiple: false });
      frame.off('select').on('select', function () {
        const a = frame.state().get('selection').first().toJSON();
        input.val(a.id);
        preview.html('<img src="' + ((a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url) + '" alt="">');
      });
      frame.open();
    });
    box.on('click', '[data-afz-clear]', function (e) { e.preventDefault(); input.val(''); preview.empty(); });
  });

  const tool = $('[data-afz-webp]');
  if (tool.length) {
    const bar = tool.find('.afz-tool__bar i'), log = tool.find('.afz-tool__log'), btn = tool.find('button');
    btn.on('click', function () {
      btn.prop('disabled', true);
      let done = 0, total = 0;
      const step = () => $.post(ajaxurl, { action: 'afz_webp_batch', _ajax_nonce: tool.data('nonce') }).done(function (r) {
        if (!r.success) { log.text(r.data || 'Error'); btn.prop('disabled', false); return; }
        done += r.data.converted; total = r.data.converted + r.data.remaining + done - r.data.converted;
        bar.css('width', total ? Math.round(done / total * 100) + '%' : '100%');
        log.text(r.data.message);
        if (r.data.remaining > 0) step(); else btn.prop('disabled', false).text('All images are WebP');
      }).fail(() => { log.text('Request failed, try again.'); btn.prop('disabled', false); });
      step();
    });
  }
});
