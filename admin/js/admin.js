/* RankPilot SEO — Admin Settings JS */
(function ($) {
	'use strict';

	var cfg = window.rpSeoAdmin || {};

	// ──────────────────────────────────────────
	// MEDIA UPLOADER (settings pages)
	// ──────────────────────────────────────────
	$(document).on('click', '.rp-media-btn', function (e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		var frame = wp.media({
			title:    cfg.selectImageText || 'Select Image',
			multiple: false,
			library:  { type: 'image' },
			button:   { text: cfg.useImageText || 'Use This Image' }
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#' + targetId).val(attachment.url).trigger('change');
			// Show preview
			var $preview = $('#' + targetId).siblings('img');
			if ($preview.length) {
				$preview.attr('src', attachment.url).show();
			} else {
				$('#' + targetId).after('<img src="' + attachment.url + '" style="max-height:60px;margin-top:4px;display:block">');
			}
		});
		frame.open();
	});

	// ──────────────────────────────────────────
	// REDIRECTS MANAGER
	// ──────────────────────────────────────────
	if ($('#rp-redirects-list').length) {
		var currentPage = 1;
		var searchQuery = '';

		loadRedirects(1, '');

		function loadRedirects(page, search) {
			currentPage = page;
			searchQuery = search;
			$('#rp-redirects-list').html('<tr><td colspan="6">Loading…</td></tr>');
			$.ajax({
				url: cfg.ajaxUrl,
				method: 'POST',
				data: {
					action: 'rp_seo_get_redirects',
					nonce:  cfg.redirectsNonce,
					page:   page,
					search: search,
				},
				success: function (res) {
					if (!res.success) return;
					renderRedirects(res.data.redirects);
					renderPagination(res.data.pages, page);
				}
			});
		}

		function renderRedirects(rows) {
			if (!rows || !rows.length) {
				$('#rp-redirects-list').html('<tr><td colspan="6">No redirects found.</td></tr>');
				return;
			}
			var html = '';
			$.each(rows, function (i, r) {
				html += '<tr>';
				html += '<td><code>' + escHtml(r.source_url) + '</code></td>';
				html += '<td><a href="' + escHtml(r.target_url) + '" target="_blank">' + escHtml(r.target_url) + '</a></td>';
				html += '<td>' + escHtml(r.redirect_type) + '</td>';
				html += '<td>' + escHtml(r.hit_count) + '</td>';
				html += '<td>' + escHtml((r.created_at || '').substring(0, 10)) + '</td>';
				html += '<td>';
				html += '<button type="button" class="button button-small rp-edit-redirect" data-id="' + r.id + '" data-source="' + escHtml(r.source_url) + '" data-target="' + escHtml(r.target_url) + '" data-type="' + r.redirect_type + '">Edit</button> ';
				html += '<button type="button" class="button button-small rp-delete-redirect" data-id="' + r.id + '" style="color:#a00">Delete</button>';
				html += '</td>';
				html += '</tr>';
			});
			$('#rp-redirects-list').html(html);
		}

		function renderPagination(totalPages, current) {
			if (totalPages <= 1) {
				$('#rp-redirects-pagination').empty();
				return;
			}
			var html = '';
			for (var p = 1; p <= totalPages; p++) {
				html += '<button type="button" class="rp-page-btn' + (p === current ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
			}
			$('#rp-redirects-pagination').html(html);
		}

		// Edit
		$(document).on('click', '.rp-edit-redirect', function () {
			$('#rp-redirect-id').val($(this).data('id'));
			$('#rp-redirect-source').val($(this).data('source'));
			$('#rp-redirect-target').val($(this).data('target'));
			$('#rp-redirect-type').val($(this).data('type'));
			$('html, body').animate({ scrollTop: 0 }, 300);
		});

		// Delete
		$(document).on('click', '.rp-delete-redirect', function () {
			if (!confirm('Delete this redirect?')) return;
			var id = $(this).data('id');
			$.ajax({
				url: cfg.ajaxUrl,
				method: 'POST',
				data: { action: 'rp_seo_delete_redirect', nonce: cfg.redirectsNonce, id: id },
				success: function (res) {
					if (res.success) loadRedirects(currentPage, searchQuery);
				}
			});
		});

		// Save
		$('#rp-save-redirect').on('click', function () {
			var id     = $('#rp-redirect-id').val();
			var source = $.trim($('#rp-redirect-source').val());
			var target = $.trim($('#rp-redirect-target').val());
			var type   = $('#rp-redirect-type').val();
			var $msg   = $('#rp-redirect-status');

			if (!source || !target) {
				$msg.attr('class', 'rp-status-msg error').text('Source and target are required.');
				return;
			}

			$.ajax({
				url: cfg.ajaxUrl,
				method: 'POST',
				data: { action: 'rp_seo_save_redirect', nonce: cfg.redirectsNonce, id: id, source: source, target: target, type: type },
				success: function (res) {
					if (res.success) {
						$msg.attr('class', 'rp-status-msg').text('Saved!');
						$('#rp-redirect-id').val('');
						$('#rp-redirect-source').val('');
						$('#rp-redirect-target').val('');
						loadRedirects(currentPage, searchQuery);
						setTimeout(function () { $msg.text(''); }, 3000);
					} else {
						$msg.attr('class', 'rp-status-msg error').text(res.data && res.data.message ? res.data.message : 'Error saving redirect.');
					}
				}
			});
		});

		// Clear form
		$('#rp-clear-form').on('click', function () {
			$('#rp-redirect-id').val('');
			$('#rp-redirect-source').val('');
			$('#rp-redirect-target').val('');
			$('#rp-redirect-type').val('301');
			$('#rp-redirect-status').text('');
		});

		// Search
		$('#rp-do-search').on('click', function () {
			loadRedirects(1, $('#rp-search-redirects').val());
		});
		$('#rp-search-redirects').on('keypress', function (e) {
			if (e.which === 13) { loadRedirects(1, $(this).val()); }
		});

		// Pagination
		$(document).on('click', '.rp-page-btn', function () {
			loadRedirects(parseInt($(this).data('page'), 10), searchQuery);
		});
	}

	function escHtml(str) {
		return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

}(jQuery));
