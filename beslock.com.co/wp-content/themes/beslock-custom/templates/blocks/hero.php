<!-- === HERO BESLOCK START (template-part) === -->
<?php /*
  Hero implemented as template-part. Uses theme-relative asset paths under
  `images/hero_develp/clips_hero` and `images/hero_develp/images_hero`.
*/ ?>
<section class="beslock-hero" id="beslockHero" aria-roledescription="carousel" aria-label="Hero carousel">
  <div class="beslock-loader" id="beslockLoader" role="status" aria-live="polite" aria-hidden="false">
    <div class="beslock-loader__bg" aria-hidden="true"></div>
    <!-- Use transparent logo (logo-white.png) and wrap to allow circular reveal + spinner -->
    <span class="beslock-loader__wrap" aria-hidden="true">
      <img class="beslock-loader__img" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-green.png' ); ?>" alt="Beslock" aria-hidden="true" />
    </span>
    <div class="beslock-loader__text" aria-hidden="true">e-Serie</div>
    <div class="beslock-loader__pulse" aria-hidden="true"></div>
  </div>

  <div class="hero-viewport" id="heroViewport" tabindex="-1">
    <div class="hero-slides" id="heroSlides">
      <?php
        // Use explicit filenames present in the repo under assets/images/Hero_develp
        $videos = array(
          'e-flex_hero.mp4',
          'e-nova_hero.mp4',
          'e-prime_hero.mp4',
          'e-shield_hero.mp4',
          'e-touch_hero.mp4',
          'e-orbit_hero.mp4',
        );
        $overlays = array(
          'e-flex_hero.png',
          'e-nova_hero.png',
          'e-prime_hero.png',
          'e-shield_hero.png',
          'e-touch_hero.png',
          'e-orbit_hero.png',
        );
        $count = min(count($videos), count($overlays));
        for ($i = 0; $i < $count; $i++):
          $vid = $videos[$i];
          $ov  = $overlays[$i];
          // Map overlay filename to high-res variant in images_hero/images_hero_d if present
          $ov_base = pathinfo($ov, PATHINFO_FILENAME);
          if (preg_match('/^(.*)_2_hero$/i', $ov_base, $m)) {
            $ov_d_file = $m[1] . '_d_2.png';
          } else {
            $ov_d_file = preg_replace('/_hero$/i', '_d', $ov_base) . '.png';
          }
          $ov_d_fs = get_stylesheet_directory() . '/assets/images/Hero_develp/images_hero/images_hero_d/' . $ov_d_file;
          $ov_d_url = file_exists($ov_d_fs) ? (get_stylesheet_directory_uri() . '/assets/images/Hero_develp/images_hero/images_hero_d/' . $ov_d_file) : '';
      ?>
      <article class="hero-slide" data-index="<?php echo $i; ?>" aria-roledescription="slide" aria-label="Slide <?php echo $i+1; ?>">
        <div class="slide-inner">
          <video class="slide-video" muted playsinline preload="auto" loop src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/Hero_develp/clips_hero/' . $vid ); ?>"></video>
          <!-- Dim layer strictly over the clip to improve white text contrast; overlays remain above -->
          <div class="slide-dim" aria-hidden="true"></div>
          <picture aria-hidden="true">
            <?php if ($ov_d_url): ?>
              <source media="(min-width:600px)" srcset="<?php echo esc_url( $ov_d_url ); ?>">
            <?php endif; ?>
            <?php
              if ($i === 2 || $i === 0 || $i === 5) {
                $data_offset_attr = ' data-offset="27"';
              } elseif ($i === 4) {
                $data_offset_attr = ' data-offset="30"';
              } else {
                $data_offset_attr = '';
              }
            ?>
            <img class="slide-overlay" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/Hero_develp/images_hero/' . $ov ); ?>"<?php echo $data_offset_attr; ?> alt="" aria-hidden="true" />
          </picture>
          <?php if ($i === 5): // Add second orbit overlay image that enters at 3.55s ?>
            <?php
              $ov2 = 'e-orbit_2_hero.png';
              $ov2_base = pathinfo($ov2, PATHINFO_FILENAME);
              if (preg_match('/^(.*)_2_hero$/i', $ov2_base, $mm)) {
                $ov2_d_file = $mm[1] . '_d_2.png';
              } else {
                $ov2_d_file = preg_replace('/_hero$/i', '_d', $ov2_base) . '.png';
              }
              $ov2_d_fs = get_stylesheet_directory() . '/assets/images/Hero_develp/images_hero/images_hero_d/' . $ov2_d_file;
              $ov2_d_url = file_exists($ov2_d_fs) ? (get_stylesheet_directory_uri() . '/assets/images/Hero_develp/images_hero/images_hero_d/' . $ov2_d_file) : '';
            ?>
              <picture aria-hidden="true">
              <?php if ($ov2_d_url): ?>
                <source media="(min-width:600px)" srcset="<?php echo esc_url( $ov2_d_url ); ?>">
              <?php endif; ?>
              <img class="slide-overlay" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/Hero_develp/images_hero/e-orbit_2_hero.png' ); ?>" data-start="3.55" data-offset="27" alt="" aria-hidden="true" />
            </picture>
          <?php endif; ?>
          <div class="slide-content">
            <?php
              // Derive a human-friendly title from the overlay filename.
              $base = pathinfo($ov, PATHINFO_FILENAME); // e-flex_hero
              $title_raw = str_replace('_', ' ', $base); // e-flex hero
              // remove the word "hero" if present and collapse whitespace
              $title_raw = preg_replace('/\bhero\b/i', '', $title_raw);
              $title_raw = trim(preg_replace('/\s+/', ' ', $title_raw));
              // capitalize the character after a hyphen (e.g. e-flex -> e-Flex)
              $pos = strpos($title_raw, '-');
              if ($pos !== false && isset($title_raw[$pos + 1])) {
                $title_raw = substr_replace($title_raw, strtoupper($title_raw[$pos + 1]), $pos + 1, 1);
              }

              // Identify product key (use the first token, e.g. 'e-flex' => 'e-flex')
              $product_key = strtolower(preg_replace('/\s+/', '', str_replace(' ', '-', $title_raw)));

              // Hero subtitle overrides (exact strings requested)
              $hero_subtitles = array(
                'e-nova'  => 'Comodidad, seguridad y tranquilidad',
                'e-flex'  => 'Llegar sin complicaciones',
                'e-prime' => 'Para todos los espacios',
                'e-touch' => 'Acceso para todos',
                'e-shield' => 'Protege lo más valioso',
                'e-orbit' => 'En cualquier lugar',
              );

              $subtitle = isset($hero_subtitles[$product_key]) ? $hero_subtitles[$product_key] : ucwords($title_raw);
            ?>
            <h1 class="hero__title"><?php echo esc_html($title_raw); ?></h1>
            <p class="hero__subtitle"><?php echo esc_html($subtitle); ?></p>

            <?php
              // Feature pool (use the same HTML structure and exact texts already present)
              $feature_pool = array(
                'visitas_temporales' => '<div class="feature"><span class="feature__icon"><img src="https://img.icons8.com/?size=100&id=26111&format=png&color=000000" alt="icon-1" /></span><div class="feature__text"><span class="feature__title">Ideal para</span><span class="feature__subtitle">visitas temporales</span></div></div>',
                'multiples_usuarios' => '<div class="feature"><span class="feature__icon"><img src="https://img.icons8.com/?size=100&id=3734&format=png&color=000000" alt="icon-2" /></span><div class="feature__text"><span class="feature__title">Múltiples</span><span class="feature__subtitle">usuarios</span></div></div>',
                'liberarte_llaves' => '<div class="feature"><span class="feature__icon"><img src="https://img.icons8.com/?size=100&id=QSpdbW6kJ2lS&format=png&color=000000" alt="icon-3" /></span><div class="feature__text"><span class="feature__title">Libérate</span><span class="feature__subtitle">de cargar llaves</span></div></div>',
                'varias_aperturas' => '<div class="feature"><span class="feature__icon"><img src="https://img.icons8.com/?size=100&id=48917&format=png&color=000000" alt="icon-4" /></span><div class="feature__text"><span class="feature__title">Varias formas</span><span class="feature__subtitle">de apertura</span></div></div>',
                'total_control' => '<div class="feature"><span class="feature__icon"><img src="https://img.icons8.com/ios/100/000000/phonelink-lock.png" alt="icon-5" /></span><div class="feature__text"><span class="feature__title">Total control</span><span class="feature__subtitle">en el celular</span></div></div>',
              );

              // Selection per product (exact required selections, order preserved)
              $features_by_product = array(
                'e-nova' => array('liberarte_llaves', 'varias_aperturas', 'multiples_usuarios'),
                'e-flex' => array('visitas_temporales', 'total_control', 'varias_aperturas'),
                'e-prime' => array('multiples_usuarios', 'total_control', 'varias_aperturas'),
                'e-touch' => array('multiples_usuarios', 'varias_aperturas', 'liberarte_llaves'),
                'e-shield' => array('liberarte_llaves', 'varias_aperturas', 'total_control'),
                'e-orbit' => array('total_control', 'varias_aperturas', 'visitas_temporales'),
              );

              // Determine which features to render for this slide
              $selected_keys = isset($features_by_product[$product_key]) ? $features_by_product[$product_key] : array_keys($feature_pool);
            ?>

            <!-- Features module (maqueta) - one per slide; layout controlled by CSS/JS -->
            <div class="features-wrapper" aria-hidden="true">
              <div class="features-list">
                <?php
                  // Render exactly the 3 features for this product, preserving existing markup
                  $rendered = 0;
                  foreach ($selected_keys as $fk) {
                    if ($rendered >= 3) break;
                    if (isset($feature_pool[$fk])) {
                      echo $feature_pool[$fk];
                      $rendered++;
                    }
                  }
                ?>
              </div>
            </div>
          </div>
        </div>
      </article>
      <?php endfor; ?>
    </div>

    <nav class="hero-dots" id="heroDots" aria-label="Carousel navigation" role="tablist">
      <?php for ($i = 1; $i <= 6; $i++): ?>
        <button class="hero-dot" data-index="<?php echo $i-1; ?>" aria-label="Go to slide <?php echo $i; ?>" role="tab"></button>
      <?php endfor; ?>
    </nav>
  </div>
</section>
<!-- === HERO BESLOCK END (template-part) === -->
