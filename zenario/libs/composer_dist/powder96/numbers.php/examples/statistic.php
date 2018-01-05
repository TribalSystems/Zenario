<?php
require_once(__DIR__ . '/../vendor/autoload.php');

// Oh it's about to get interesting.

echo '<pre>';

// Consider a data representing total follower count of a
// variety of users.
$followers = array(100, 50, 1000, 39, 283, 634, 3, 6123);

// We can generate a report of summary statistics
// which includes the mean, 1st and 3rd quartiles,
// and standard deviation.
$report = \NumbersPHP\Statistic::report($followers);
echo 'Report = ' . var_export($report, true) . "\n";

// Maybe we decide to become a bit more curious about
// trends in follower count, so we start conjecturing about
// our ability to "predict" trends.
// Let's consider the number of tweets those users have.
$tweets = array(100, 10, 400, 5, 123, 24, 302, 2000);

// Let's calculate the correlation.
$correlation = \NumbersPHP\Statistic::correlation($tweets, $followers);
echo 'Correlation between tweets and followers: ' . $correlation . "\n";

// Now let's create a linear regression.
$linReg = \NumbersPHP\Statistic::linearRegression($tweets, $followers);

// $linReg is actually a function we can use to map tweets
// onto followers. We'll see that around 1422 followers
// are expected if a user tweets 500 times.
$estFollowers = $linReg(500);
echo 'Estimated number of followers if a user tweets 500 times: ' . $estFollowers;

echo '</pre>';