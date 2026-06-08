=== RankPilot SEO ===
Contributors: rankpilot
Tags: seo, sitemap, breadcrumbs, schema, open graph, redirect manager, woocommerce seo, meta tags
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Full-featured SEO plugin: meta optimization, XML sitemaps, breadcrumbs, schema markup, redirect manager, social previews, AI generation, and WooCommerce SEO.

== Description ==

RankPilot SEO is a comprehensive search engine optimization plugin for WordPress, inspired by industry-leading SEO tools. It covers everything from on-page meta optimization to technical SEO.

= Core Features =

**On-Page SEO**
* SEO meta box on every post, page, and custom post type
* Custom SEO title (with character counter + progress bar)
* Custom meta description (with length guidance)
* Focus keyword + synonyms/related keyphrase support
* Real-time SEO analysis: keyword in title, description, URL, content density, word count, featured image
* Readability analysis: sentence length, paragraph length, passive voice, Flesch score
* Live search snippet preview (desktop + mobile mode)
* Per-post noindex/nofollow/noarchive/nosnippet robot controls
* Custom canonical URL override
* Custom breadcrumb title per post
* Exclude individual posts from sitemap

**Social & Open Graph**
* Open Graph meta tags (og:title, og:description, og:image, og:type, og:url, og:site_name, article:*)
* Twitter/X Card meta tags (summary, summary_large_image)
* Per-post Facebook title, description, and image override
* Per-post Twitter title, description, and image override
* Default OG image fallback
* Facebook App ID support
* Social preview in the post editor (Facebook card mockup)

**XML Sitemaps**
* Sitemap index at /sitemap.xml
* Separate sitemaps for posts, pages, products, and terms
* Image sitemaps (Google Image extension)
* Automatic ping to Google and Bing on publish
* Exclude noindexed posts automatically
* Exclude specific post IDs
* Configurable posts per sitemap

**Breadcrumbs**
* Hierarchical breadcrumb trail
* Shortcode: [rankpilot_breadcrumbs]
* Template function: rankpilot_breadcrumb()
* BreadcrumbList JSON-LD schema output
* WooCommerce product breadcrumbs with primary category
* Customizable separator, home label, prefix, and styling

**Structured Data / Schema.org**
* WebSite schema with SearchAction (sitelinks searchbox)
* Organization or Person schema with sameAs social profiles
* Article, BlogPosting, NewsArticle, WebPage schema per content type
* Product schema with price, availability, ratings, brand, and SKU
* BreadcrumbList schema
* Per-post schema type override (Article, FAQPage, HowTo, Event, etc.)

**Redirect Manager**
* 301, 302, 307 redirects
* Add/edit/delete redirects from admin UI
* Search through redirects
* Hit count tracking
* Auto-detect URL changes when post slugs are modified

**WooCommerce SEO**
* Product schema with price, stock, ratings, and brand
* Product SEO analysis checks (short description, gallery, alt text, SKU)
* Replace WooCommerce default breadcrumbs
* Exclude cart/checkout/my-account from sitemap
* Primary category selection for breadcrumbs
* Product gallery as OG image fallback

**AI Content Generation**
* Generate SEO titles and meta descriptions with AI
* Uses Anthropic Claude API (configurable API key)
* Falls back to rule-based generation when API is not configured

**Technical SEO**
* Remove WordPress generator meta tag
* Custom title templates with %%title%%, %%sitename%%, %%sep%%, %%term_title%% tokens
* Noindex for search pages, 404s, date archives, attachment pages
* REST API endpoints for settings and analysis

== Installation ==

1. Upload the `rankpilot-seo` folder to `/wp-content/plugins/`
2. Activate the plugin via the WordPress Plugins menu
3. Navigate to **RankPilot SEO** in the admin sidebar to configure

== Frequently Asked Questions ==

= How do I add breadcrumbs to my theme? =

Add `<?php rankpilot_breadcrumb(); ?>` to your theme template, or use the shortcode `[rankpilot_breadcrumbs]` in any post/page.

= How do I enable AI generation? =

Go to **RankPilot SEO → General** and enter your Anthropic API key. You can then use the ⚡ AI buttons in the post editor to generate titles and descriptions.

= Where is the sitemap? =

Your sitemap index is at `yourdomain.com/sitemap.xml`. If it 404s, go to **Settings → Permalinks** and click Save to flush rewrite rules.

= Does this work with WooCommerce? =

Yes. When WooCommerce is active, a dedicated WooCommerce SEO settings page becomes available with product schema, breadcrumb integration, and sitemap controls.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
