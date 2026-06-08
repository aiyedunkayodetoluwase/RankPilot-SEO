/* RankPilot SEO — Meta Box JS */
(function ($) {
	'use strict';

	var i18n    = rpSeoMetaBox.i18n || {};
	var restUrl = rpSeoMetaBox.restUrl;
	var nonce   = rpSeoMetaBox.restNonce;
	var postId  = $('#post_ID').val();

	// ──────────────────────────────────────────
	// TABS
	// ──────────────────────────────────────────
	$(document).on('click', '.rp-seo-tab', function () {
		var tab = $(this).data('tab');
		$('.rp-seo-tab').removeClass('active');
		$('.rp-seo-tab-content').removeClass('active');
		$(this).addClass('active');
		$('#rp-tab-' + tab).addClass('active');
	});

	// ──────────────────────────────────────────
	// SNIPPET PREVIEW MODE
	// ──────────────────────────────────────────
	$(document).on('click', '.rp-snippet-mode', function () {
		$('.rp-snippet-mode').removeClass('active');
		$(this).addClass('active');
		var mode = $(this).data('mode');
		$('.rp-snippet-preview').toggleClass('mobile-mode', mode === 'mobile');
	});

	// ──────────────────────────────────────────
	// LIVE SNIPPET UPDATE
	// ──────────────────────────────────────────
	function updatePreview() {
		var title = $('#rp_seo_title').val() || (document.title ? document.title.split(' | ')[0] : '');
		var desc  = $('#rp_seo_description').val();
		$('#rp-preview-title').text(title || i18n.noKeyword);
		$('#rp-preview-desc').text(desc);
	}

	$('#rp_seo_title').on('input', updatePreview);
	$('#rp_seo_description').on('input', updatePreview);

	// ──────────────────────────────────────────
	// CHARACTER COUNTERS
	// ──────────────────────────────────────────
	function updateCharCount(input, countEl, barEl, min, max) {
		var len  = $(input).val().length;
		var pct  = Math.min(100, (len / max) * 100);
		$(countEl).text(len + '/' + max);

		if (len > max) {
			$(countEl).attr('class', 'rp-char-count over');
			$(barEl).css('width', '100%').attr('class', 'rp-progress-fill');
		} else if (len >= min && len <= max) {
			$(countEl).attr('class', 'rp-char-count ok');
			$(barEl).css('width', pct + '%').attr('class', 'rp-progress-fill good');
		} else {
			$(countEl).attr('class', 'rp-char-count');
			$(barEl).css('width', pct + '%').attr('class', 'rp-progress-fill' + (len > 0 ? ' ok' : ''));
		}
	}

	$('#rp_seo_title').on('input', function () {
		updateCharCount(this, '#rp-title-count', '#rp-title-bar', 30, 60);
		debouncedAnalyze();
	});

	$('#rp_seo_description').on('input', function () {
		updateCharCount(this, '#rp-desc-count', '#rp-desc-bar', 70, 158);
		debouncedAnalyze();
	});

	$('#rp_seo_focus_keyword').on('input', debouncedAnalyze);

	// Init on load
	updateCharCount('#rp_seo_title', '#rp-title-count', '#rp-title-bar', 30, 60);
	updateCharCount('#rp_seo_description', '#rp-desc-count', '#rp-desc-bar', 70, 158);
	updatePreview();

	// ──────────────────────────────────────────
	// SEO ANALYSIS via REST API
	// ──────────────────────────────────────────
	var analyzeTimer = null;
	function debouncedAnalyze() {
		clearTimeout(analyzeTimer);
		analyzeTimer = setTimeout(runAnalysis, 800);
	}

	function runAnalysis() {
		if (!postId) return;
		var keyword = $('#rp_seo_focus_keyword').val().trim();
		if (!keyword) {
			renderChecks([{ type: 'na', msg: i18n.noKeyword }], 'na');
			return;
		}

		$.ajax({
			url: restUrl + 'analyze',
			method: 'POST',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nonce);
			},
			data: JSON.stringify({
				post_id:          parseInt(postId, 10),
				focus_keyword:    keyword,
				seo_title:        $('#rp_seo_title').val(),
				meta_description: $('#rp_seo_description').val(),
			}),
			contentType: 'application/json',
			success: function (data) {
				if (data && data.checks) {
					renderChecks(data.checks, data.grade);
				}
			}
		});
	}

	function renderChecks(checks, grade) {
		var html = '';
		$.each(checks, function (i, c) {
			html += '<li class="rp-check rp-' + (c.type || 'na') + '">';
			html += '<span class="rp-dot"></span>';
			html += '<span>' + escapeHtml(c.msg || '') + '</span>';
			html += '</li>';
		});
		$('#rp-seo-analysis').html(html || '<li class="rp-check rp-na"><span class="rp-dot"></span> —</li>');
		updateScoreBadge('#rp-score-badge', grade);
	}

	function updateScoreBadge(el, grade) {
		var label = grade === 'good' ? i18n.good : (grade === 'ok' ? i18n.ok : (grade === 'poor' ? i18n.poor : 'N/A'));
		var cls   = 'rp-score-dot rp-score-' + (grade || 'na');
		$(el).find('.rp-score-dot').attr('class', cls);
		$(el).find('.rp-score-label').text(label);
	}

	// Run on load if keyword exists
	if ($('#rp_seo_focus_keyword').val()) {
		debouncedAnalyze();
	}

	// ──────────────────────────────────────────
	// READABILITY ANALYSIS (basic, client-side)
	// ──────────────────────────────────────────
	function runReadabilityAnalysis() {
		// Pull content from TinyMCE or textarea
		var content = '';
		if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
			content = tinymce.get('content').getContent({ format: 'text' });
		} else {
			content = $('#content').val() || '';
		}

		if (!content.trim()) {
			$('#rp-readability-analysis').html('<li class="rp-check rp-na"><span class="rp-dot"></span> ' + escapeHtml('No content to analyze.') + '</li>');
			return;
		}

		var checks   = [];
		var sentences = content.split(/[.!?]+/).filter(function(s){ return s.trim().length > 0; });
		var words     = content.split(/\s+/).filter(function(w){ return w.trim().length > 0; });
		var paragraphs = content.split(/\n\s*\n/).filter(function(p){ return p.trim().length > 0; });

		// Sentence length
		var longSentences = sentences.filter(function(s){ return s.split(/\s+/).length > 20; });
		var longPct = sentences.length > 0 ? (longSentences.length / sentences.length * 100).toFixed(0) : 0;
		if (longPct > 25) {
			checks.push({ type: 'warning', msg: longPct + '% of sentences are too long (>20 words). Aim for max 25%.' });
		} else {
			checks.push({ type: 'good', msg: 'Sentence length looks good (' + longPct + '% long sentences).' });
		}

		// Paragraph length
		var longParas = paragraphs.filter(function(p){ return p.split(/\s+/).length > 150; });
		if (longParas.length > 0) {
			checks.push({ type: 'warning', msg: longParas.length + ' paragraph(s) are too long. Consider splitting them.' });
		} else {
			checks.push({ type: 'good', msg: 'Paragraph length is good.' });
		}

		// Word count
		if (words.length >= 300) {
			checks.push({ type: 'good', msg: 'Text is ' + words.length + ' words. Good length.' });
		} else {
			checks.push({ type: 'warning', msg: 'Text is only ' + words.length + ' words. Aim for at least 300.' });
		}

		// Passive voice (simple heuristic)
		var passivePattern = /\b(is|are|was|were|be|been|being)\s+\w+ed\b/gi;
		var passiveMatches  = content.match(passivePattern) || [];
		var passivePct = sentences.length > 0 ? (passiveMatches.length / sentences.length * 100).toFixed(0) : 0;
		if (passivePct > 10) {
			checks.push({ type: 'warning', msg: 'Too many passive voice sentences (' + passivePct + '%). Aim for max 10%.' });
		} else {
			checks.push({ type: 'good', msg: 'Passive voice usage is fine (' + passivePct + '%).' });
		}

		// Subheadings
		var hasHeadings = /<h[2-4]/i.test(
			typeof tinymce !== 'undefined' && tinymce.get('content')
				? tinymce.get('content').getContent()
				: ''
		);
		if (words.length > 300) {
			if (hasHeadings) {
				checks.push({ type: 'good', msg: 'Text uses subheadings to structure the content.' });
			} else {
				checks.push({ type: 'warning', msg: 'No subheadings found. Use H2/H3 to break up long content.' });
			}
		}

		// Flesch score (approximation)
		var syllables = 0;
		words.forEach(function(w){ syllables += estimateSyllables(w); });
		var flesch = 0;
		if (sentences.length > 0 && words.length > 0) {
			flesch = 206.835 - 1.015 * (words.length / sentences.length) - 84.6 * (syllables / words.length);
			flesch = Math.max(0, Math.min(100, flesch));
		}
		var fleschLabel = flesch >= 80 ? 'Very easy' : (flesch >= 60 ? 'Easy' : (flesch >= 50 ? 'Fairly easy' : (flesch >= 30 ? 'Difficult' : 'Very difficult')));
		$('#rp-flesch-fill').css('width', flesch + '%').css('background-color', flesch >= 60 ? '#38a169' : (flesch >= 40 ? '#f6ad55' : '#e53e3e'));
		$('#rp-flesch-label').text(fleschLabel + ' (' + Math.round(flesch) + ')');

		// Render
		var html = '';
		$.each(checks, function(i, c) {
			html += '<li class="rp-check rp-' + c.type + '"><span class="rp-dot"></span><span>' + escapeHtml(c.msg) + '</span></li>';
		});
		$('#rp-readability-analysis').html(html);

		var goodCount = checks.filter(function(c){ return c.type === 'good'; }).length;
		var grade = goodCount >= checks.length * 0.7 ? 'good' : (goodCount >= checks.length * 0.4 ? 'ok' : 'poor');
		updateScoreBadge('#rp-readability-badge', grade);
	}

	function estimateSyllables(word) {
		word = word.toLowerCase().replace(/[^a-z]/g, '');
		if (word.length <= 3) return 1;
		word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '');
		word = word.replace(/^y/, '');
		var matches = word.match(/[aeiouy]{1,2}/g);
		return matches ? matches.length : 1;
	}

	// Run readability when switching to that tab
	$(document).on('click', '.rp-seo-tab[data-tab="readability"]', function () {
		setTimeout(runReadabilityAnalysis, 100);
	});

	// ──────────────────────────────────────────
	// AI GENERATION
	// ──────────────────────────────────────────
	$('#rp-generate-title').on('click', function () {
		var $btn = $(this);
		$btn.addClass('loading').text(i18n.generating || 'Generating…');
		$.ajax({
			url: restUrl + 'generate',
			method: 'POST',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nonce);
			},
			data: JSON.stringify({
				post_id:       parseInt(postId, 10),
				type:          'title',
				focus_keyword: $('#rp_seo_focus_keyword').val(),
			}),
			contentType: 'application/json',
			success: function (data) {
				if (data && data.generated) {
					$('#rp_seo_title').val(data.generated).trigger('input');
				}
			},
			error: function () {
				alert(i18n.generateError || 'Generation failed.');
			},
			complete: function () {
				$btn.removeClass('loading').html('&#9889; AI');
			}
		});
	});

	$('#rp-generate-desc').on('click', function () {
		var $btn = $(this);
		$btn.addClass('loading').text(i18n.generating || 'Generating…');
		$.ajax({
			url: restUrl + 'generate',
			method: 'POST',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', nonce);
			},
			data: JSON.stringify({
				post_id:       parseInt(postId, 10),
				type:          'description',
				focus_keyword: $('#rp_seo_focus_keyword').val(),
			}),
			contentType: 'application/json',
			success: function (data) {
				if (data && data.generated) {
					$('#rp_seo_description').val(data.generated).trigger('input');
				}
			},
			error: function () {
				alert(i18n.generateError || 'Generation failed.');
			},
			complete: function () {
				$btn.removeClass('loading').html('&#9889; AI');
			}
		});
	});

	// ──────────────────────────────────────────
	// MEDIA LIBRARY
	// ──────────────────────────────────────────
	var mediaFrame;
	$(document).on('click', '.rp-media-btn', function (e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		if (mediaFrame) { mediaFrame.open(); return; }
		mediaFrame = wp.media({
			title:    'Select Image',
			multiple: false,
			library:  { type: 'image' },
			button:   { text: 'Use This Image' }
		});
		mediaFrame.on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			$('#' + targetId).val(attachment.url).trigger('change');
		});
		mediaFrame.open();
	});

	// Social preview updates
	$('#rp_seo_og_title').on('input', function () {
		$('#rp-fb-title').text($(this).val() || $('#rp_seo_title').val() || '');
	});
	$('#rp_seo_og_description').on('input', function () {
		$('#rp-fb-desc').text($(this).val() || $('#rp_seo_description').val() || '');
	});

	// ──────────────────────────────────────────
	// UTILS
	// ──────────────────────────────────────────
	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

}(jQuery));
