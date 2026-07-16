=== My Article Listener ===
Contributors: ammar458
Tags: text to speech, accessibility, audio, speech synthesis
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Your own free "Listen to this article" player, powered by the browser's built-in speech engine.

== Description ==

My Article Listener adds a "Listen to this article" player to your posts using the browser's built-in speech synthesis engine. No external service, no fees, no accounts, no monthly limits.

Features:

* Settings page with label, sub-label, and accent color
* Optional reading of the post title before the article body
* Per-post on/off control from the post editor
* Draggable progress bar and estimated listening time
* Replay button to restart the article from the beginning

== Changelog ==

= 1.7 =
* Added: the progress bar is now a draggable seek control, so visitors can rewind or skip ahead. Since the browser speech engine can't seek mid-sentence, dragging jumps to the nearest ~200-character sentence group.
* Added: a replay button that restarts the article from the beginning at any point during playback.

= 1.6 =
* Changed: removed the voice picker and playback speed dropdowns. The player now always uses the "Mark" voice when the visitor's browser/OS provides one (falling back to a voice matching the page language, then the first available voice), at a fixed 1x speed.

= 1.5 =
* Improvement: short all-caps acronyms (SEO, PPC, ROI, etc.) are now spelled out letter by letter (e.g. "S.E.O.") instead of being read as if they were a single word.

= 1.4 =
* Fix: on pages with a "related posts" or "recent posts" grid widget (Elementor's Posts widget), the content-detection logic could pick a short excerpt from one of those teaser cards instead of the actual post body, since they also match generic selectors like `article`. Teaser/grid-item cards are now explicitly excluded from consideration.
* Added `.elementor-widget-theme-post-content` as an always-tried fallback selector, in addition to whatever is configured in Settings, since it's the correct wrapper on Elementor "Single Post" templates.

= 1.3 =
* Fix: the player could end up reading only the post title when multiple elements on the page matched the content selector (e.g. a related-post teaser with the same class as the article wrapper). It now checks every matching element and uses whichever contains the most text.

= 1.2 =
* Current release.
