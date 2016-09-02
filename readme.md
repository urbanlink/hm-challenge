# Haagse Makers Challenge Wordpress plugin

## About
Create a list with the stuff you want to learn and make. Share it with others.
Wordpress plugin.

## How does is work?
A registered user can create a new list (challenge) and add up to 100 items to this list. A new list is created by going to the page /maker-challenge. This is the landing page with the challenge-list for the logged in user.
After creating the first item, some metadata is added to the user profile:

  * *challenge_active* (boolean) Flag to see if the challenge is activated for this user or not.
  * *challenge_active_date* (date) The date the challenge was activated.
  * *challenge_mailing_step* (integer) Variable to store which mailing-step

Based on these values, a cronjob sends update mails to the user about the challenge. 

## Installation and uninstall
Upon installation a new post type is created: challenge item.
Create a page '/maker-challenge' and use the shortcode [hm_challenge_index] to create the challenge landing page.
Registered users can now create new challenge items.

## Content type

## Mailings
