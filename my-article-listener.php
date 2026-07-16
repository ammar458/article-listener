<?php
/**
 * Plugin Name: My Article Listener
 * Description: Your own free "Listen to this article" player. Uses the browser's built-in speech engine, so there are no fees, no accounts, and no monthly limits. Includes a settings page, voice picker, speed control, progress bar, and per-post on/off control.
 * Version: 1.4
 * Author: You
 * License: GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------
 * 0. AUTO-UPDATER (checks GitHub releases)
 * ------------------------------------------------- */

require_once __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$mal_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/ammar458/article-listener/',
	__FILE__,
	'my-article-listener'
);

/* -------------------------------------------------
 * 1. SETTINGS PAGE
 * ------------------------------------------------- */

function mal_default_options() {
	return array(
		'label'        => 'Listen to this article',
		'sub_label'    => 'Free audio, powered by your browser',
		'read_title'   => 1,
		'enable_all'   => 1,
		'accent'       => '#1a1a1a',
		'content_sel'  => '.elementor-widget-theme-post-content',
	);
}

function mal_get_options() {
	return wp_parse_args( get_option( 'mal_options', array() ), mal_default_options() );
}

function mal_register_settings() {
	register_setting( 'mal_group', 'mal_options', array(
		'sanitize_callback' => function ( $input ) {
			$d = mal_default_options();
			return array(
				'label'       => sanitize_text_field( $input['label'] ?? $d['label'] ),
				'sub_label'   => sanitize_text_field( $input['sub_label'] ?? $d['sub_label'] ),
				'read_title'  => empty( $input['read_title'] ) ? 0 : 1,
				'enable_all'  => empty( $input['enable_all'] ) ? 0 : 1,
				'accent'      => sanitize_hex_color( $input['accent'] ?? $d['accent'] ) ?: $d['accent'],
				'content_sel' => sanitize_text_field( $input['content_sel'] ?? $d['content_sel'] ),
			);
		},
	) );
}
add_action( 'admin_init', 'mal_register_settings' );

function mal_admin_menu() {
	add_options_page( 'My Article Listener', 'Article Listener', 'manage_options', 'mal-settings', 'mal_settings_page' );
}
add_action( 'admin_menu', 'mal_admin_menu' );

function mal_settings_page() {
	$o = mal_get_options();
	?>
	<div class="wrap">
		<h1>My Article Listener</h1>
		<p>Your own free listen-to-article player. No external service, no limits.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mal_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mal_label">Player label</label></th>
					<td><input type="text" id="mal_label" name="mal_options[label]" value="<?php echo esc_attr( $o['label'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="mal_sub">Sub label</label></th>
					<td><input type="text" id="mal_sub" name="mal_options[sub_label]" value="<?php echo esc_attr( $o['sub_label'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Read post title first</th>
					<td><label><input type="checkbox" name="mal_options[read_title]" value="1" <?php checked( $o['read_title'], 1 ); ?>> Start the audio by reading the headline</label></td>
				</tr>
				<tr>
					<th scope="row">Show on all posts</th>
					<td><label><input type="checkbox" name="mal_options[enable_all]" value="1" <?php checked( $o['enable_all'], 1 ); ?>> Enable the player on every post automatically (you can still turn it off per post from the post editor)</label></td>
				</tr>
				<tr>
					<th scope="row"><label for="mal_accent">Accent color</label></th>
					<td><input type="text" id="mal_accent" name="mal_options[accent]" value="<?php echo esc_attr( $o['accent'] ); ?>" class="small-text" placeholder="#1a1a1a"></td>
				</tr>
				<tr>
					<th scope="row"><label for="mal_sel">Content CSS selector</label></th>
					<td>
						<input type="text" id="mal_sel" name="mal_options[content_sel]" value="<?php echo esc_attr( $o['content_sel'] ); ?>" class="regular-text">
						<p class="description">Advanced: which element holds your article text. The default works with most themes.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------
 * 2. PER-POST ON/OFF META BOX
 * ------------------------------------------------- */

function mal_add_meta_box() {
	add_meta_box( 'mal_box', 'Article Listener', 'mal_meta_box_html', 'post', 'side' );
}
add_action( 'add_meta_boxes', 'mal_add_meta_box' );

function mal_meta_box_html( $post ) {
	$mode = get_post_meta( $post->ID, '_mal_mode', true ) ?: 'default';
	wp_nonce_field( 'mal_save', 'mal_nonce' );
	?>
	<p>
		<label><input type="radio" name="mal_mode" value="default" <?php checked( $mode, 'default' ); ?>> Use global setting</label><br>
		<label><input type="radio" name="mal_mode" value="on" <?php checked( $mode, 'on' ); ?>> Always show player</label><br>
		<label><input type="radio" name="mal_mode" value="off" <?php checked( $mode, 'off' ); ?>> Hide player on this post</label>
	</p>
	<?php
}

function mal_save_meta( $post_id ) {
	if ( ! isset( $_POST['mal_nonce'] ) || ! wp_verify_nonce( $_POST['mal_nonce'], 'mal_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	$mode = in_array( $_POST['mal_mode'] ?? '', array( 'default', 'on', 'off' ), true ) ? $_POST['mal_mode'] : 'default';
	update_post_meta( $post_id, '_mal_mode', $mode );
}
add_action( 'save_post', 'mal_save_meta' );

/* -------------------------------------------------
 * 3. SHOULD THE PLAYER APPEAR?
 * ------------------------------------------------- */

function mal_should_show() {
	if ( ! is_singular( 'post' ) ) return false;
	$o    = mal_get_options();
	$mode = get_post_meta( get_the_ID(), '_mal_mode', true ) ?: 'default';
	if ( 'on' === $mode )  return true;
	if ( 'off' === $mode ) return false;
	return (bool) $o['enable_all'];
}

/* -------------------------------------------------
 * 4. INJECT THE PLAYER
 * ------------------------------------------------- */

function mal_player_html( $content ) {
	if ( ! in_the_loop() || ! is_main_query() || ! mal_should_show() ) return $content;
	$o = mal_get_options();

	$player = '
	<div id="mal-player" aria-label="' . esc_attr( $o['label'] ) . '">
		<button id="mal-toggle" type="button" aria-label="Play article audio">
			<svg id="mal-ic-play" width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M4 2.5v11l9-5.5-9-5.5z"/></svg>
			<svg id="mal-ic-pause" width="15" height="15" viewBox="0 0 16 16" fill="currentColor" style="display:none"><path d="M4 2h3v12H4zM9 2h3v12H9z"/></svg>
		</button>
		<div class="mal-mid">
			<div class="mal-toprow">
				<span class="mal-label">' . esc_html( $o['label'] ) . '</span>
				<span class="mal-time" id="mal-time"></span>
			</div>
			<div class="mal-bar"><div class="mal-fill" id="mal-fill"></div></div>
			<span class="mal-sub" id="mal-sub">' . esc_html( $o['sub_label'] ) . '</span>
		</div>
		<div class="mal-ctrls">
			<select id="mal-voice" aria-label="Voice"></select>
			<select id="mal-speed" aria-label="Playback speed">
				<option value="0.8">0.8x</option>
				<option value="1" selected>1x</option>
				<option value="1.25">1.25x</option>
				<option value="1.5">1.5x</option>
				<option value="2">2x</option>
			</select>
		</div>
	</div>';

	return $player . $content;
}
add_filter( 'the_content', 'mal_player_html' );

/* -------------------------------------------------
 * 5. STYLES AND SCRIPT
 * ------------------------------------------------- */

function mal_assets() {
	if ( ! mal_should_show() ) return;
	$o      = mal_get_options();
	$accent = esc_html( $o['accent'] );
	$sel    = wp_json_encode( $o['content_sel'] );
	?>
	<style>
		#mal-player{display:flex;align-items:center;gap:14px;padding:12px 18px;margin:0 0 26px;
			border:1px solid #e0e0e0;border-radius:14px;background:#fbfbfb;max-width:560px;font-family:inherit}
		#mal-toggle{width:44px;height:44px;flex:0 0 44px;border:none;border-radius:50%;
			background:<?php echo $accent; ?>;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:opacity .15s}
		#mal-toggle:hover{opacity:.82}
		.mal-mid{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}
		.mal-toprow{display:flex;justify-content:space-between;align-items:baseline;gap:8px}
		.mal-label{font-weight:600;font-size:14px}
		.mal-time{font-size:11px;color:#888;white-space:nowrap}
		.mal-bar{height:4px;border-radius:2px;background:#e6e6e6;overflow:hidden}
		.mal-fill{height:100%;width:0;background:<?php echo $accent; ?>;transition:width .4s linear}
		.mal-sub{font-size:11.5px;color:#8a8a8a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
		.mal-ctrls{display:flex;flex-direction:column;gap:6px}
		.mal-ctrls select{border:1px solid #d5d5d5;border-radius:7px;padding:3px 5px;font-size:12px;background:#fff;cursor:pointer;max-width:120px}
		@media (max-width:480px){.mal-ctrls{flex-direction:row}#mal-voice{max-width:90px}}
	</style>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var player = document.getElementById('mal-player');
		if (!player) return;
		if (!('speechSynthesis' in window)) { player.style.display = 'none'; return; }

		var toggle  = document.getElementById('mal-toggle');
		var icPlay  = document.getElementById('mal-ic-play');
		var icPause = document.getElementById('mal-ic-pause');
		var sub     = document.getElementById('mal-sub');
		var fill    = document.getElementById('mal-fill');
		var timeEl  = document.getElementById('mal-time');
		var speedEl = document.getElementById('mal-speed');
		var voiceEl = document.getElementById('mal-voice');

		function getBodyText(root) {
			var clone = root.cloneNode(true);
			clone.querySelectorAll('#mal-player,script,style,figure,figcaption,iframe,form,nav,aside,h1,.wp-block-embed,.sharedaddy,.jp-relatedposts,.comments-area,.elementor-posts-container,.elementor-grid-item').forEach(function (el) { el.remove(); });
			return (clone.innerText || clone.textContent || '').replace(/\s+/g, ' ').trim();
		}

		// Try the configured selector plus sensible fallbacks; if several elements match
		// (e.g. a related-post teaser using the same class), keep whichever has the most
		// text, since that's almost always the real article body.
		var selectors = [<?php echo $sel; ?>, '.elementor-widget-theme-post-content', '.elementor-widget-theme-post-content .elementor-widget-container', '.entry-content', 'article', 'main'];
		var bodyText = '';
		selectors.forEach(function (sel) {
			document.querySelectorAll(sel).forEach(function (el) {
				// Skip related-post / grid teaser cards - they match generic tags like
				// "article" but are excerpts of OTHER posts, not this post's own content.
				if (el.closest('.elementor-grid-item,.elementor-posts-container,.jp-relatedposts,.sharedaddy')) return;
				var text = getBodyText(el);
				if (text.length > bodyText.length) bodyText = text;
			});
		});

		// Read the page's H1 as the title. Falls back to the WordPress post title.
		var readTitle = <?php echo $o['read_title'] ? 'true' : 'false'; ?>;
		var postTitle = '';
		if (readTitle) {
			var h1 = document.querySelector('h1');
			postTitle = h1 ? h1.innerText.trim() : <?php echo wp_json_encode( get_the_title() ); ?>;
		}
		var fullText = (postTitle ? postTitle + '. ' : '') + bodyText;
		fullText = fullText.trim();
		if (!fullText) { player.style.display = 'none'; return; }

		// Split into ~200 char sentence groups so long articles never get cut off
		var parts = fullText.match(/[^.!?]+[.!?]+|\S[^.!?]*$/g) || [fullText];
		var groups = [], buf = '';
		parts.forEach(function (s) {
			if ((buf + s).length > 200) { if (buf) groups.push(buf.trim()); buf = s; }
			else { buf += s; }
		});
		if (buf.trim()) groups.push(buf.trim());

		var totalChars = fullText.length;
		var idx = 0, playing = false, rate = 1, chosenVoice = null;
		var currentU = null;   // Persistent reference: prevents Chrome from garbage-collecting the utterance, which silently stops playback after the first chunk
		var keepAlive = null;  // Works around Chrome pausing long speech after ~15 seconds

		function startKeepAlive() {
			stopKeepAlive();
			keepAlive = setInterval(function () {
				if (speechSynthesis.speaking && !speechSynthesis.paused) {
					speechSynthesis.pause();
					speechSynthesis.resume();
				}
			}, 10000);
		}
		function stopKeepAlive() {
			if (keepAlive) { clearInterval(keepAlive); keepAlive = null; }
		}

		// Rough duration estimate: average speech is about 15 chars per second at 1x
		function estMinutes() {
			var mins = Math.max(1, Math.round(totalChars / (15 * rate) / 60));
			return mins + ' min listen';
		}
		timeEl.textContent = estMinutes();

		function loadVoices() {
			var voices = speechSynthesis.getVoices();
			if (!voices.length) return;
			var docLang = (document.documentElement.lang || 'en').slice(0, 2).toLowerCase();
			var matching = voices.filter(function (v) { return v.lang.slice(0, 2).toLowerCase() === docLang; });
			var list = matching.length ? matching : voices;
			voiceEl.innerHTML = '';
			list.forEach(function (v) {
				var op = document.createElement('option');
				op.value = v.name;
				op.textContent = v.name.replace(/(Microsoft|Google)\s*/i, '').split(' ')[0] + ' (' + v.lang + ')';
				voiceEl.appendChild(op);
			});
			chosenVoice = list[0] || null;
		}
		loadVoices();
		speechSynthesis.onvoiceschanged = loadVoices;

		voiceEl.addEventListener('change', function () {
			var voices = speechSynthesis.getVoices();
			chosenVoice = voices.find(function (v) { return v.name === voiceEl.value; }) || null;
			if (playing) restart();
		});

		function setUI(isPlaying, msg) {
			playing = isPlaying;
			icPlay.style.display  = isPlaying ? 'none' : 'block';
			icPause.style.display = isPlaying ? 'block' : 'none';
			if (msg) sub.textContent = msg;
		}

		function updateProgress() {
			var done = 0;
			for (var i = 0; i < idx; i++) done += groups[i].length;
			fill.style.width = Math.min(100, Math.round((done / totalChars) * 100)) + '%';
		}

		function speakNext() {
			if (idx >= groups.length) {
				idx = 0;
				fill.style.width = '100%';
				stopKeepAlive();
				setUI(false, 'Finished. Tap play to listen again.');
				return;
			}
			var myIdx = idx;
			var u = new SpeechSynthesisUtterance(groups[idx]);
			u.rate = rate;
			if (chosenVoice) u.voice = chosenVoice;

			var advanced = false;
			function advance() {
				if (advanced || !playing || myIdx !== idx) return;
				advanced = true;
				idx++;
				updateProgress();
				speakNext();
			}

			u.onend = advance;
			// Watchdog: if Chrome drops the onend event, move on anyway once the chunk's estimated time has clearly passed
			var estMs = (groups[idx].length / (15 * rate)) * 1000 + 3000;
			setTimeout(function () {
				if (playing && !advanced && myIdx === idx && !speechSynthesis.speaking) advance();
			}, estMs);

			u.onerror = function (e) {
				if (e.error === 'interrupted' || e.error === 'canceled') return;
				stopKeepAlive();
				setUI(false, 'Playback error. Try another voice.');
			};

			currentU = u; // keep reference alive
			speechSynthesis.speak(u);
			sub.textContent = 'Now playing';
		}

		// Chrome can silently ignore speak() called right after cancel(); a short delay avoids that
		function restart() {
			speechSynthesis.cancel();
			setTimeout(function () { if (playing) speakNext(); }, 120);
		}

		toggle.addEventListener('click', function () {
			if (playing) {
				playing = false;
				speechSynthesis.cancel();
				stopKeepAlive();
				setUI(false, 'Paused');
			} else {
				setUI(true);
				speechSynthesis.cancel();
				startKeepAlive();
				setTimeout(speakNext, 120);
			}
		});

		speedEl.addEventListener('change', function () {
			rate = parseFloat(speedEl.value);
			timeEl.textContent = estMinutes();
			if (playing) restart();
		});

		window.addEventListener('beforeunload', function () { speechSynthesis.cancel(); });
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'mal_assets' );

/* -------------------------------------------------
 * 6. SETTINGS LINK ON THE PLUGINS PAGE
 * ------------------------------------------------- */

function mal_action_links( $links ) {
	array_unshift( $links, '<a href="' . admin_url( 'options-general.php?page=mal-settings' ) . '">Settings</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mal_action_links' );
