<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure Drupal is properly installed');
$I->amOnPage("/");
$I->see("Welcome to Rock Solid", "h1");
