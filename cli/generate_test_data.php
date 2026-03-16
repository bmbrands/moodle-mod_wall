<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI script to generate test comment data for mod_wall.
 *
 * Generates a 9gag-style comment thread about a picture of 3 fluffy
 * Highland cows (Schotse Hooglanders). Comments are in English and Dutch.
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Top-level comments about 3 fluffy Highland cows.
$toplevelcomments = [
    "Omg those fluffy cows look like they have better hair than me on a good day 😭",
    "Schotse hooglanders zijn letterlijk de meest knuffelbare koeien ter wereld",
    "When you and the squad show up to the party looking fabulous 🐮🐮🐮",
    "These cows have more volume in their hair than a L'Oréal commercial",
    "Ik wil er eentje als huisdier. Mijn vrouw zegt nee. Ik overweeg een scheiding.",
    "POV: you just used the entire bottle of conditioner",
    "They look like 3 metal guitarists headbanging in a field 🎸",
    "Waarom zien deze koeien eruit alsof ze beter gekapt zijn dan ik?",
    "Highland cows are proof that God has a sense of humor AND good taste",
    "The middle one looks like it's about to drop the hottest album of 2026",
    "Drie dikke fluffy bois. Ik kan niet meer. Te schattig.",
    "They're not fluffy, they're ✨ voluminous ✨",
    "If my hair looked this good I would never complain again",
    "Schotse hooglanders in Nederland = harige immigranten, maar iedereen vindt ze geweldig",
    "The left one has serious 'did you just take my photo without permission' energy",
    "I showed this to my barber and said 'give me the highland cow'",
    "Deze beesten zien eruit alsof ze hun eigen Instagram account hebben",
    "Three floofy bois living their absolute best life. Respect.",
    "Tag yourself, I'm the one in the middle who can't see anything through their fringe",
    "Fun fact: Schotse hooglanders zijn eigenlijk heel lief. Ze laten je gewoon even aaien.",
    "These cows are more photogenic than me in every single one of my profile pics",
    "Koeien met betere kapsels dan de gemiddelde Nederlander. Normaal.",
    "OK but where do they get their hair done, asking for a friend",
    "This is the content I subscribed for 🐄❤️",
    "Mijn oma heeft deze koeien in Drenthe. Ze noemt ze Hans, Grietje en Dikkie.",
    "Plot twist: they're actually 3 mops disguised as cows",
    "The one on the right looks like it just heard the juiciest gossip",
    "Ik zou serieus een hele middag naar deze koeien kunnen kijken",
    "Imagine being this majestic and not even knowing it",
    "Highland cows are just emo cows and I'm here for it 🖤",
    "Ze zien er warm uit. Wil ook zo'n jas.",
    "The holy trinity of floof 🙏",
    "When your mom says you all look handsome before the family photo",
    "Hooglanders > elke andere koe. Verander mijn mening.",
    "I want to hug all three of them simultaneously",
    "Dit is waarom ik het internet gebruik. Niet voor nieuws. Hiervoor.",
    "They look like they're posing for a boy band album cover",
    "Serious question: can you brush a highland cow? Because I volunteer",
    "Drie fluffy koeien in een weiland. Dit is piek-internet.",
    "The fact that these absolute units exist makes the world a better place",
];

// Reply comments - reactions to the top-level comments.
$replies = [
    "HAHAHA ik ben dood 💀",
    "This is the best thing I've seen all week",
    "Underrated comment ^^",
    "Nee maar serieus, dit klopt 😂",
    "I literally spat out my coffee reading this",
    "Helemaal mee eens, 100%",
    "Can confirm, have tried. Would recommend.",
    "Why is this so accurate though",
    "Ik lach al 5 minuten om deze comment",
    "Someone give this person an award 🏆",
    "This comment wins the internet today",
    "Waarom is dit zo grappig 😂😂😂",
    "Take my upvote you beautiful human",
    "I feel personally attacked by this comment",
    "Hahaha mijn collega kijkt me raar aan omdat ik zo hard lach",
    "OK this one got me, not gonna lie",
    "Ik wou dat ik awards kon geven",
    "Living for this energy right now",
    "Bruh 😭😭😭",
    "Geef deze man een standbeeld",
    "Finally someone who gets it",
    "Ik kan niet meer, stuur hulp",
    "This is why I come to the comments section",
    "W comment",
    "Je snapt het. Je snapt het gewoon.",
    "Not me reading this at 3am wheezing",
    "Same energy tbh",
    "Helemaal waar, geen woord gelogen",
    "I've been scrolling for 10 minutes and this is the best one",
    "Haha ja precies dit",
    "Subscribed just for this comment section honestly",
    "Maat, ik schreeuw het uit 🤣",
    "Bold take but I fully support it",
    "Now I can't unsee it 😂",
    "Oké dit is goud, puur goud",
    "You woke up and chose violence with this comment and I respect that",
    "Ik ga stuk, dankjewel internet",
    "The real content is always in the comments",
    "Dit moet de top comment worden",
    "I'm saving this comment for when I'm sad",
];

// Get CLI options.
[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'wallid' => null,
        'comments' => 20,
    ],
    [
        'h' => 'help',
        'w' => 'wallid',
        'c' => 'comments',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['wallid']) {
    $help = <<<EOT
Generate test data for mod_wall - 9gag style comments about fluffy Highland cows.

Options:
-h, --help          Print out this help
-w, --wallid        Wall instance ID (required)
-c, --comments      Number of top-level comments (default: 20, max: 40)

Example:
\$ php mod/wall/cli/generate_test_data.php --wallid=1 --comments=30

EOT;
    echo $help;
    exit(0);
}

$wallid = (int)$options['wallid'];
$commentcount = min((int)$options['comments'], count($toplevelcomments));

// Verify wall instance exists.
$wall = $DB->get_record('wall', ['id' => $wallid]);
if (!$wall) {
    cli_error("Wall instance with ID {$wallid} not found.");
}

$cm = get_coursemodule_from_instance('wall', $wallid);
if (!$cm) {
    cli_error("Course module not found for Wall instance with ID {$wallid}.");
}
$context = context_module::instance($cm->id);

// Get enrolled users with comment capability.
$users = get_enrolled_users($context, 'mod/wall:comment', 0, 'u.id, u.firstname, u.lastname');
if (empty($users)) {
    cli_error("No users with comment capability enrolled in this course.");
}
$users = array_values($users);

cli_heading("Generating Highland Cow comment wall for: {$wall->name}");
cli_writeln("Enrolled commenters: " . count($users));
cli_writeln("Top-level comments to create: {$commentcount}");
cli_writeln('');

// Shuffle comments for variety.
shuffle($toplevelcomments);
$toplevelcomments = array_slice($toplevelcomments, 0, $commentcount);

$totalcomments = 0;
$totalreplies = 0;
$totalvotes = 0;
$createdids = [];

// Spread comments over the last 48 hours for realism.
$now = time();
$basetime = $now - (48 * 3600);

foreach ($toplevelcomments as $i => $commenttext) {
    // Pick a random user as author.
    $author = $users[array_rand($users)];

    // Comments appear at intervals across the 48h window.
    $timecreated = $basetime + (int)(($i / $commentcount) * (48 * 3600)) + rand(0, 600);

    $comment = new stdClass();
    $comment->wallid = $wallid;
    $comment->userid = $author->id;
    $comment->parentid = 0;
    $comment->content = $commenttext;
    $comment->contentformat = FORMAT_HTML;
    $comment->timecreated = $timecreated;
    $comment->timemodified = $timecreated;
    $comment->usermodified = $author->id;
    $comment->id = $DB->insert_record('wall_comment', $comment);

    $createdids[] = $comment->id;
    $totalcomments++;

    cli_writeln("[{$author->firstname}] {$commenttext}");

    // 60% chance of getting 1-4 replies.
    if (rand(1, 100) <= 60) {
        $replycount = rand(1, 4);
        $shuffledreplies = $replies;
        shuffle($shuffledreplies);

        for ($r = 0; $r < $replycount; $r++) {
            // Pick a different user for the reply if possible.
            $replier = $users[array_rand($users)];
            $replytime = $timecreated + rand(60, 7200);

            $reply = new stdClass();
            $reply->wallid = $wallid;
            $reply->userid = $replier->id;
            $reply->parentid = $comment->id;
            $reply->content = $shuffledreplies[$r];
            $reply->contentformat = FORMAT_HTML;
            $reply->timecreated = $replytime;
            $reply->timemodified = $replytime;
            $reply->usermodified = $replier->id;
            $reply->id = $DB->insert_record('wall_comment', $reply);

            $createdids[] = $reply->id;
            $totalreplies++;

            cli_writeln("  ↳ [{$replier->firstname}] {$shuffledreplies[$r]}");
        }
    }
}

// Generate votes on comments if voting is enabled.
if ($wall->enablevoting) {
    cli_writeln('');
    cli_writeln("Generating votes...");

    foreach ($createdids as $commentid) {
        // Each comment gets votes from 0-70% of users.
        $votercount = rand(0, (int)(count($users) * 0.7));
        $shuffledusers = $users;
        shuffle($shuffledusers);

        for ($v = 0; $v < $votercount; $v++) {
            $voter = $shuffledusers[$v];

            // Don't let users vote on their own comments.
            $commentauthor = $DB->get_field('wall_comment', 'userid', ['id' => $commentid]);
            if ($voter->id == $commentauthor) {
                continue;
            }

            // 75% upvote, 25% downvote — 9gag energy is mostly positive on cute animals.
            $vote = (rand(1, 100) <= 75) ? 1 : -1;
            $votetime = $now - rand(0, 48 * 3600);

            $voterecord = new stdClass();
            $voterecord->commentid = $commentid;
            $voterecord->userid = $voter->id;
            $voterecord->vote = $vote;
            $voterecord->timecreated = $votetime;
            $voterecord->timemodified = $votetime;

            try {
                $DB->insert_record('wall_vote', $voterecord);
                $totalvotes++;
            } catch (dml_write_exception $e) {
                // Duplicate vote, skip.
            }
        }
    }
}

cli_writeln('');
cli_heading("Summary");
cli_writeln("Top-level comments: {$totalcomments}");
cli_writeln("Replies: {$totalreplies}");
cli_writeln("Total comments: " . ($totalcomments + $totalreplies));
cli_writeln("Votes: {$totalvotes}");
cli_writeln('');
cli_writeln("Done! 🐮");
