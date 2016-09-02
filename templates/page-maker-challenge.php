<div class="page-header">
  <div class="container">
    <div class="col-xs-12">
      <h1>De Haagse Makers Challenge</h1>
      <h2>Wat ga jij maken?</h2>
    </div>
  </div>
</div>

<div class="page-content">
  <div class="container">
    <div class="col-xs-12">
      <p>Hoe kunnen we dit nog beter maken dan nu? Door nieuwe dingen te maken! We willen meer zijn dan <em>"gewoon een consument"</em>. Dingen maken, en deze dingen naar buiten brengen, geeft zin en voldoening. </p>
      <p>We dagen onszelf uit om 100 dingen te maken/leren. Doe je mee? Maak ook een lijst, en laat zien wat je wilt maken! Het hoeven niet per se 100 dingen te zijn (dat is ons doel); We willen onszelf graag uitdagen om meer de tijd te nemen om te maken. </p>
      <hr>
    </div>

    <div class="col-xs-12">
      <div class="row">
        <main class="col-xs-12 col-sm-8">
        <?php if ( !is_user_logged_in() ) { ?>

            <div class="challenge-visitor-block">
              <h2>Doe mee aan de maker challenge</h2>
              <p>Start met de Haagse Makers challenge, de stimulans voor jezelf om nieuwe dingen te maken. Maak je lijst op Haagse Makers!</p>
              <br>
              <a href="/login" class="btn btn-primary">Inloggen en aanmelden voor de challenge</a>
              <br>
              <hr>
            </div>
          <?php } else { ?>
            <div class="challenge-add-form">
              <h2>Jouw Maker Challenge</h2>
              <form id="challenge-add-form" class="form-inline">
                <div class="form-group">
                  <input id="challenge-item" class="form-control" type="text" placeholder="Wat ga jij maken? "></item>
                </div>
                <button id="challenge-submit" class="btn btn-primary">Toevoegen</button>
              </form>
            </div>

            <?php $active = get_the_author_meta('hm_challenge_active');
            if (!$active) { ?>
              <div class="challenge-user-inactive">
                <br>
                <p>Je hebt nog geen actieve maker challenge. Voeg je eerste item toe dat je wilt gaan maken om de challenge te starten.</p>
              </div>
            <?php } ?>
            <div class="challenge-user-active">
              <?php global $current_user;
              get_currentuserinfo();

              // The Query arguments
              $args = array(
                'post_count' => 150,
                'nopaging' => true,
                'post_type' => 'challenge_item',
                'author' => $current_user->ID,
                'order' => 'ASC'
              );

              // The Query
              $the_query = new WP_Query( $args );

              // The Loop
              if ( $the_query->have_posts() ) { ?>
                <ol id="challenge-list">
                  <?php while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    $status = get_field('challenge_status');

                    if ($status) {
                      $status = 'checked';
                    } else {
                      $status = 'false';
                    }

                    echo '<li class="challenge-listitem ' . $status . '">
                      <input type="checkbox" id="' . get_the_id() . $status . '"></input>' .
                      get_the_title() .
                      ' <a href class="remove" id="' . get_the_id() . '"> <i class="fa fa-trash remove"></i></a>
                    </li>';
                  } ?>
                </ol>
              <?php } else { ?>
                <span class="empty">Je hebt nog geen items aan je lijst toegevoegd. Wat ga jij maken?</span>
                <ol id="challenge-list"></ol>
              <?php }
              /* Restore original Post Data */
              wp_reset_postdata();
              ?>
            </div>
          <?php } ?>
        </main>
          <aside class="col-xs-12 col-sm-4">
            <?php if (is_user_logged_in()) { ?>
              <div class="userlist">
                <?php echo do_shortcode('[hmchallenge-userlist]'); ?>
              </div>
            <?php } ?>

            <?php if (is_user_logged_in()) { ?>
              <div class="randomlist">
                <?php echo do_shortcode('[hmchallenge-random-items]'); ?>
              </div>
            <?php } ?>
          </aside>
        </div>
      </div>
    </div>
  </div>
</div>
