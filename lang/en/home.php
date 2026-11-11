<?php

/*
 * Home: the authenticated landing page.
 */

return [

    'head' => 'Home',

    // Chosen in the browser's time zone; :name is the learner's first name.
    'greeting' => [
        'morning' => 'Good morning, :name',
        'afternoon' => 'Good afternoon, :name',
        'evening' => 'Good evening, :name',
    ],

    'empty' => [
        'title' => 'Your lessons are on their way',
        'body' => 'Lessons, practice and exams will appear here as each section opens. There is nothing to do yet — come back soon.',
    ],

];
