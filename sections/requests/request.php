<?

/*
 * This is the page that displays the request to the end user after being created.
 */

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

if(empty($_GET['id']) || !is_number($_GET['id'])) { 
	error(0);
}

$RequestID = $_GET['id'];

//First things first, lets get the data for the request.

$Request = get_requests(array($RequestID));	
$Request = $Request['matches'][$RequestID];
if(empty($Request)) {
	error(404);
}

list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Image, $Description,
     $FillerID, $FillerName, $TorrentID, $TimeFilled, $GroupID) = $Request;

//Convenience variables
$IsFilled = !empty($TorrentID);
$CanVote = (empty($TorrentID) && check_perms('site_vote'));

if($CategoryID == 0) {
    $CategoryName = 'unknown';
} else {
    $CategoryName = $NewCategories[$CategoryID]['name'];
}

$FullName = $Title;
$DisplayLink = $Title;

//Votes time
$RequestVotes = get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0)));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

show_header('View request: '.$FullName, 'comments,requests,bbcode,jquery');

?>
<div class="thin">
	<h2><a href="requests.php">Requests</a> &gt; <?=$CategoryName?> &gt; <?=$DisplayLink?></h2>
	<div class="linkbox">
<? if($CanEdit) { ?> 
		<a href="requests.php?action=edit&amp;id=<?=$RequestID?>">[Edit]</a>
<? }
if($UserCanEdit || check_perms('users_mod')) { //check_perms('site_moderate_requests')) { ?>
		<a href="requests.php?action=delete&amp;id=<?=$RequestID?>">[Delete]</a>
<? } ?>
<?	if(has_bookmarked('request', $RequestID)) { ?>
		<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Unbookmark('request', <?=$RequestID?>,'[Bookmark]');return false;">[Remove bookmark]</a>
<?	} else { ?>
		<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Bookmark('request', <?=$RequestID?>,'[Remove bookmark]');return false;">[Bookmark]</a>
<?	} ?>
		<a href="reports.php?action=report&amp;type=request&amp;id=<?=$RequestID?>">[Report Request]</a>
		<a href="upload.php?requestid=<?=$RequestID?><?=($GroupID?"&groupid=$GroupID":'')?>">[Upload Request]</a>
<? if(!$IsFilled && $CategoryID == 0) { ?>
		<a href="reports.php?action=report&amp;type=request_update&amp;id=<?=$RequestID?>">[Request Update]</a>
<? } ?>
	</div>
	
	<div class="sidebar">
<? if($CategoryID != 0) { ?>
                <div class="head"><strong>Cover</strong></div>
		<div class="box box_albumart center">
<?	if (!empty($Image)) { ?>
                <img style="max-width: 220px;" src="<?=$Image?>" alt="<?=$FullName?>" onclick="lightbox.init(this,220);" />
<?	} else { ?>
                <img src="<?=STATIC_SERVER?>common/noartwork/noimage.png" alt="<?=$CategoryName?>" title="<?=$CategoryName?>" width="220" height="220" border="0" />
<?	} ?>
		</div>
<? } ?>

                <div class="head"><strong>Tags</strong></div>
		<div class="box box_tags">
			<ul class="stats nobullet">
<?	foreach($Request['Tags'] as $TagID => $TagName) { ?>
				<li>
					<a href="torrents.php?taglist=<?=$TagName?>"><?=display_str($TagName)?></a>
					<br style="clear:both" />
				</li>
<?	} ?>
			</ul>
		</div>
                <div class="head"><strong>Top Contributors</strong></div> 
			<table class="box box_votes">
<?	$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
	$ViewerVote = false;
	for($i = 0; $i < $VoteMax; $i++) { 
		$User = array_shift($RequestVotes['Voters']);
		$Boldify = false;
		if ($User['UserID'] == $LoggedUser['ID']) {
			$ViewerVote = true;
			$Boldify = true;
		}
?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><?=$Boldify?'<strong>':''?><?=display_str($User['Username'])?><?=$Boldify?'</strong>':''?></a>
					</td>
					<td>
						<?=$Boldify?'<strong>':''?><?=get_size($User['Bounty'])?><?=$Boldify?'</strong>':''?>
					</td>
				</tr>
<?	} 
	reset($RequestVotes['Voters']);
	if (!$ViewerVote) {
		foreach ($RequestVotes['Voters'] as $User) {
			if ($User['UserID'] == $LoggedUser['ID']) { ?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><strong><?=display_str($User['Username'])?></strong></a>
					</td>
					<td>
						<strong><?=get_size($User['Bounty'])?></strong>
					</td>
				</tr>
<?			}
		}
	}
?>
			</table>
	</div>
      <div class="main_column">
          
          
            <div class="head"><?=$DisplayLink?></div>
		<table>
			<tr>
				<td class="label">Created</td>
				<td>
					<?=time_diff($TimeAdded)?>	by  <strong><?=format_username($RequestorID, $RequestorName)?></strong>
				</td>
			</tr>

<?	if ($GroupID) { ?>
			<tr>
				<td class="label">Torrent Group</td>
				<td><a href="torrents.php?id=<?=$GroupID?>">torrents.php?id=<?=$GroupID?></td>
			</tr>
<?	} ?>
			<tr>
				<td class="label">Votes</td>
				<td>
					<span id="votecount"><?=$VoteCount?></span> 
<?	if($CanVote) { ?>
					&nbsp;<a href="javascript:Vote(0)"><strong>(+)</strong></a>
					<strong>Costs <?=get_size($MinimumVote, 0)?></strong>
<?	} ?> 
				</td>
			</tr>
<?	if($CanVote) { ?>
			<tr id="voting">
				<td class="label">Custom Vote</td>
				<td>
					<input type="text" id="amount_box" size="8" onchange="Calculate();" />
					<select id="unit" name="unit" onchange="Calculate();">
						<option value='mb'>MB</option>
						<option value='gb'>GB</option>
                                                <option value='tb'>TB</option>
					</select>
					<input type="button" value="Preview" onclick="Calculate();"/>
				</td>
			</tr>
			<tr>
				<td class="label">Post vote information</td>
				<td>
					<form action="requests.php" method="get" id="request_form">
						<input type="hidden" name="action" value="vote" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="requestid" name="id" value="<?=$RequestID?>" />
						<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="amount" name="amount" value="0" />
                                                <input type="hidden" id="readable" name="readable" value="" />
						<input type="hidden" id="current_uploaded" value="<?=$LoggedUser['BytesUploaded']?>" />
						<input type="hidden" id="current_downloaded" value="<?=$LoggedUser['BytesDownloaded']?>" />
						<input id="total_bounty" type="hidden" value="<?=$RequestVotes['TotalBounty']?>" />
						If you add the entered <strong><span id="new_bounty">0.00 MB</span></strong> of bounty, your new stats will be: <br/>
						Uploaded: <span id="new_uploaded"><?=get_size($LoggedUser['BytesUploaded'])?></span>
						Ratio: <span id="new_ratio"><?=ratio($LoggedUser['BytesUploaded'],$LoggedUser['BytesDownloaded'])?></span>
						<input type="button" id="button" value="Vote!" disabled="disabled" onclick="Vote();"/> 
					</form>
				</td>
			</tr>
<? }?> 
			<tr id="bounty">
				<td class="label">Bounty</td>
				<td id="formatted_bounty"><?=get_size($RequestVotes['TotalBounty'])?></td>
			</tr>
<?
	if($IsFilled) {
		$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
?>
			<tr>
				<td class="label">Filled</td>
				<td>
					<strong><a href="torrents.php?<?=(strtotime($TimeFilled)<$TimeCompare?'id=':'torrentid=').$TorrentID?>">Yes</a></strong>, 
					by user <?=format_username($FillerID, $FillerName)?>
<?		if($LoggedUser['ID'] == $RequestorID || $LoggedUser['ID'] == $FillerID || check_perms('site_moderate_requests')) { ?>
						<strong><a href="requests.php?action=unfill&amp;id=<?=$RequestID?>">(Unfill)</a></strong> Unfilling a request without a valid, nontrivial reason will result in a warning. 
<?		} ?>
				</td>
			</tr>
<?	} else { ?>
			<tr>
				<td class="label" valign="top">Fill request</td>
				<td>
					<form action="" method="post">
						<div>
							<input type="hidden" name="action" value="takefill" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="hidden" name="requestid" value="<?=$RequestID?>" />
							<input type="text" size="50" name="link" <?=(!empty($Link) ? "value='$Link' " : '')?>/>
							<br />
                                                        <strong>Should be the permalink (PL) to the torrent (e.g. http://<?=NONSSL_SITE_URL?>/torrents.php?id=xxxx).</strong>
							<br />
							<br />
							<? if(check_perms('site_moderate_requests')) { ?> For User: <input type="text" size="25" name="user" <?=(!empty($FillerUsername) ? "value='$FillerUsername' " : '')?>/>
							<br />
							<? } ?>
							<input type="submit" value="Fill request" />
							<br /> 
						</div>
					</form>
					
				</td>
			</tr>
<?	} ?>
			<tr>
				<td colspan="2" class="center"><strong>Description</strong></td>
			</tr>
			<tr>
                      <td colspan="2"><?=$Text->full_format($Description, get_permissions_advtags($RequestorID))?></td>
			</tr>
		</table>
<?

$Results = $Cache->get_value('request_comments_'.$RequestID);
if($Results === false) {
	$DB->query("SELECT
			COUNT(c.ID)
			FROM requests_comments as c
			WHERE c.RequestID = '$RequestID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('request_comments_'.$RequestID, $Results, 0);
}

list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID);
if($Catalogue === false) {
	$DB->query("SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
			FROM requests_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
            LEFT JOIN users_main AS a ON a.ID = c.AuthorID
			WHERE c.RequestID = '$RequestID'
			ORDER BY c.ID
			LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)%THREAD_CATALOGUE),TORRENT_COMMENTS_PER_PAGE,true);
?>
	<div class="linkbox"><a name="comments"></a>
<?
$Pages=get_pages($Page,$Results,TORRENT_COMMENTS_PER_PAGE,9,'#comments');
echo $Pages;
?>
	</div>
      <div class="head">Comments</div>
<?

//---------- Begin printing
foreach($Thread as $Key => $Post){
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Donor, $Warned, $Avatar, $Enabled, $UserTitle,,,$Signature) = array_values(user_info($AuthorID));
      $AuthorPermissions = get_permissions($PermissionID);
      list($ClassLevel,$PermissionValues,$MaxSigLength,$MaxAvatarWidth,$MaxAvatarHeight)=array_values($AuthorPermissions);
      ?>
<table class="forum_post  vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>" id="post<?=$PostID?>">
	<tr class="smallhead">
		<td colspan="2">
			<span style="float:left;"><a href='#post<?=$PostID?>'>#<?=$PostID?></a>
				<?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled, $PermissionID,$UserTitle,true)?> <?=time_diff($AddedTime)?> <a href="reports.php?action=report&amp;type=requests_comment&amp;id=<?=$PostID?>">[Report Comment]</a>
				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','r<?=$RequestID?>','<?=$Username?>');">[Quote]</a>
<?if (($AuthorID == $LoggedUser['ID'] && time_ago($AddedTime)<USER_EDIT_POST_TIME) || check_perms('site_moderate_forums')){ ?>				
                        - <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>','<?=$Key?>');">[Edit]</a><? }
if (check_perms('site_admin_forums')){ ?>				
                        - <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a> <? } ?>
			</span>
			<span id="bar<?=$PostID?>" style="float:right;">
				<a href="#">&uarr;</a>
			</span>
		</td>
	</tr>
	<tr>
<?  if(empty($HeavyInfo['DisableAvatars'])) { ?>
		<td class="avatar" valign="top">
	<? if ($Avatar) { ?>
			<img src="<?=$Avatar?>" class="avatar" style="<?=get_avatar_css($MaxAvatarWidth, $MaxAvatarHeight)?>" alt="<?=$Username ?>'s avatar" />
	<? } else { ?>
			<img src="<?=STATIC_SERVER?>common/avatars/default.png" class="avatar" style="<?=get_avatar_css(100, 120)?>" alt="Default avatar" />
	<?
         } 
        $UserBadges = get_user_badges($AuthorID); 
        if( !empty($UserBadges) ) {  ?>
               <div class="badges">
<?                  print_badges_array($UserBadges, $AuthorID); ?>
               </div>
<?      }      ?>
		</td>
<?
} 
$AllowTags= get_permissions_advtags($AuthorID, false, $AuthorPermissions);
?>
		<td class="body" valign="top">
			<div id="content<?=$PostID?>">
                      <div class="post_content"><?=$Text->full_format($Body, $AllowTags) ?> </div>
<? if($EditedUserID){ ?>
                        <div class="post_footer">
<?	if(check_perms('site_moderate_forums')) { ?>
				<a href="#content<?=$PostID?>" onclick="LoadEdit('requests', <?=$PostID?>, 1); return false;">&laquo;</a> 
<? 	} ?>
                        <span class="editedby">Last edited by
				<?=format_username($EditedUserID, $EditedUsername) ?> <?=time_diff($EditedTime,2,true,true)?>
                        </span>
                        </div>
<? } ?>
			</div>
		</td>
	</tr>
<? /*
      if( empty($HeavyInfo['DisableSignatures']) && ($MaxSigLength > 0) && !empty($Signature) ) { //post_footer
                        
            echo '
      <tr>
            <td class="sig"><div id="sig"><div>' . $Text->full_format($Signature, $AllowTags) . '</div></div></td>
      </tr>';
           } */
?>
</table>
<?	} ?>
       
		<div class="linkbox">
		<?=$Pages?>
		</div>
<?
if(!$LoggedUser['DisablePosting']) { ?>
			<br />
			<div class="messagecontainer" id="container"><div id="message" class="hidden center messagebar"></div></div>
                  <div class="head">Post reply</div>
				<table id="quickreplypreview" class="hidden forum_post box vertical_margin" id="preview">
					<tr class="smallhead">
						<td colspan="2">
							<span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
								<?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'] == 2 ? false : true, $LoggedUser['PermissionID'],$LoggedUser['Title'],true)?> 
								Just now
							</span>
							<span id="barpreview" style="float:right;">
								<a href="#quickreplypreview">[Report]</a>
								<a href="#">&uarr;</a>
							</span>
						</td>
					</tr>
					<tr>
						<td class="avatar" valign="top">
				<? if (!empty($LoggedUser['Avatar'])) { ?>
							<img src="<?=$LoggedUser['Avatar']?>" class="avatar" style="<?=get_avatar_css($LoggedUser['MaxAvatarWidth'], $LoggedUser['MaxAvatarHeight'])?>" alt="<?=$LoggedUser['Username']?>'s avatar" />
				<? } else { ?>
							<img src="<?=STATIC_SERVER?>common/avatars/default.png" class="avatar" style="<?=get_avatar_css(100, 120)?>" alt="Default avatar" />
				<? } ?>
						</td>
						<td class="body" valign="top">
							<div id="contentpreview" style="text-align:left;"></div>
						</td>
					</tr>
				</table>
			<div class="box pad shadow">
				<form id="quickpostform" action="" method="post" onsubmit="return Validate_Form('message', 'quickpost')" style="display: block; text-align: center;">
					<div id="quickreplytext">
						<input type="hidden" name="action" value="reply" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="requestid" value="<?=$RequestID?>" />
                                    <? $Text->display_bbcode_assistant("quickpost", get_permissions_advtags($LoggedUser['ID'], $LoggedUser['CustomPermissions'])); ?>
                                    <textarea id="quickpost" name="body" class="long" rows="8"></textarea> <br />
					</div>
					<input id="post_preview" type="button" value="Preview" onclick="if(this.preview){Quick_Edit();}else{Quick_Preview();}" />
					<input type="submit" value="Post comment" />
				</form>
			</div>
<? } ?>
	</div>
</div>
<? show_footer(); ?>
