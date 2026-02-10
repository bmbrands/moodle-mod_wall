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
 * French language pack for Wall
 *
 * @package    mod_wall
 * @category   string
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Mur';
$string['modulenameplural'] = 'Murs';
$string['pluginadministration'] = 'Administration du mur';
$string['pluginname'] = 'Mur';
$string['privacy:metadata'] = 'Le plugin Mur stocke les commentaires publiés par les utilisateurs.';
$string['privacy:metadata:wall_comment'] = 'Commentaires publiés sur un mur.';
$string['privacy:metadata:wall_comment:content'] = 'Le contenu du commentaire.';
$string['privacy:metadata:wall_comment:timecreated'] = 'La date de création du commentaire.';
$string['privacy:metadata:wall_comment:userid'] = 'L\'identifiant de l\'utilisateur qui a publié le commentaire.';
$string['wall:addinstance'] = 'Ajouter un nouveau mur';
$string['wall:comment'] = 'Ajouter des commentaires au mur';
$string['wall:deletecomment'] = 'Supprimer n\'importe quel commentaire';
$string['wall:view'] = 'Voir le mur';

// UI strings.
$string['addcomment'] = 'Ajouter un commentaire';
$string['cancel'] = 'Annuler';
$string['commentby'] = 'Commentaire de {$a}';
$string['comments'] = 'Commentaires';
$string['confirmdeletecomment'] = 'Êtes-vous sûr de vouloir supprimer ce commentaire ?';
$string['deletecomment'] = 'Supprimer le commentaire';
$string['downvote'] = 'Vote négatif';
$string['enablevoting'] = 'Activer les votes';
$string['enablevotinghelp'] = 'Permettre aux utilisateurs de voter pour ou contre les commentaires';
$string['hidereplies'] = 'Masquer les réponses';
$string['invalidparentcomment'] = 'Commentaire parent invalide';
$string['nocomments'] = 'Pas encore de commentaires. Soyez le premier à commenter !';
$string['oncoursepage'] = 'Afficher le mur sur la page du cours';
$string['oncoursepagehelp'] = 'Lorsque cette option est activée, le mur de commentaires sera affiché directement sur la page du cours';
$string['reply'] = 'Répondre';
$string['upvote'] = 'Vote positif';
$string['viewreplies'] = 'Voir {$a} réponses';
$string['votingnotenabled'] = 'Les votes ne sont pas activés pour ce mur';

// Events.
$string['eventcommentcreated'] = 'Commentaire créé';
$string['eventcommentdeleted'] = 'Commentaire supprimé';

// Notifications.
$string['messageprovider:comment_reply'] = 'Quelqu\'un a répondu à votre commentaire';
$string['notification_reply_html'] = '<p>{$a->replyuser} a répondu à votre commentaire sur <a href="{$a->link}">{$a->wallname}</a>.</p>';
$string['notification_reply_small'] = '{$a->replyuser} a répondu à votre commentaire sur {$a->wallname}';
$string['notification_reply_subject'] = '{$a->replyuser} a répondu à votre commentaire';
