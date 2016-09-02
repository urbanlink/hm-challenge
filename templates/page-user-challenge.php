<?php

global $wp_query;
$slug = $wp_query->query_vars['slug'];
$user = get_user_by('slug', $slug);
$user_id = $user->ID;

// send to 404 if no user
if (!$user) { return; }

// Get challenge meta
if (!get_the_author_meta('hm_challenge_active', $user_id)) {
  echo '<p>This user is not active in the challenge. </p>';
  return;
}
$challenge_startdate = get_the_author_meta('hm_challenge_startdate', $user_id);
$challenge_metastep = get_the_author_meta('hm_challenge_mailingstep', $user_id);

?>

<div class="challenge challenge-user page-header">
  <div class="container">
    <div class="col-xs-12">
      <h1>De Haagse Makers Challenge</h1>
      <h2>Dit is de challenge van: <?php echo $user->display_name;?></h2>
    </div>
  </div>
</div>
<div class="page-content">
  <div class="container">
    <div class="row">
      <main class="col-xs-12 col-sm-8">
        <?php
        // The Query arguments
        $args = array(
          'post_count' => 150,
          'nopaging' => true,
          'post_type' => 'challenge_item',
          'author' => $user_id,
          'order' => 'ASC'
        );

        // The Query
        $the_query = new WP_Query( $args );

        // The Loop
        if ( $the_query->have_posts() ) {
          ?>
          <h3><?php echo $user->display_name;?> gaat dit maken de komende tijd: </h3>
          <ul id="challenge-list">
            <?php
            while ( $the_query->have_posts() ) {
              $the_query->the_post();

              $status = get_field('challenge_status');
              if ($status) { $status = 'checked'; }
              echo '<li class="challenge-listitem ' . $status . '">' . get_the_title() . '</li>';
            } ?>
          </ul>
          <?php
        } else {
          // no posts found
          ?>
          <span class="empty">Je hebt nog geen items aan je lijst toegevoegd. Wat ga jij maken?</span>
          <ol id="challenge-list"></ol>
        <?php
        }
        /* Restore original Post Data */
        wp_reset_postdata();
        ?>
      </main>
      <aside class="col-xs-12 col-sm-4">
        <div class="userlist">
          <?php echo do_shortcode('[hmchallenge-userlist]'); ?>
        </div>
      </aside>
    </div>
  </div>
  </div>
</div>
