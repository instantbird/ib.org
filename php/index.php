<?php include('header.inc.php');
$org_bundle = new bundle('instantbird.org');
echo '
  <div id="page">

<h1>'.strtr($org_bundle->_('instantbirdBecause'), array('Instantbird' => '<a href="http://www.instantbird.com/">Instantbird</a>')).__('</h1>
    <h2 style="text-align:center">_(weBelieveImportantComponentOnlineLife)</h2>

<h2 class="borderbottom">_(weBelieve)</h2>
<ul>
<li>_(deservesMoreAttention)</li>
<li>_(solidAlternative)</li>
<li>_(reclaimControl)</li>
<li>_(shouldBeEasyReliableFunEmpowering)
 <ul>
  <li>_(shouldJustWork)</li>
  <li>_(relyOnIt)</li>
  <li>_(customizeIt)</li>
 </ul>
</li>
<li>_(respectTheUser):
 <ul>
  <li><strong>_(privacy)</strong>: _(privacyText)</li>
  <li><strong>_(freedom)</strong>: _(freedomText)</li>
 </ul>
 <blockquote><p>_(openSource)</p></blockquote>
</li>

<li>_(shouldBeAccessibleAdapted):
<blockquote><p>_(adaptedToYourNeeds)</p></blockquote>
</li>
</ul>

<p>_(bringBackInnovation)</p>

<h2 class="borderbottom"><a name="getinvolved" class="no-link">_(gettingInvolved)</a></h2>

<p>_(joiningUs)</p>

<ul>

<li>_(feedbackAndIdeas)
 <p>_(knowHowItWorksForYou)</p>
 <p>_(severalWaysToSendFeedback)</p>
<p>_(comeDiscussYourIdeas)</p>
</li>
<li>_(spreadTheWord)

 <p>_(talkAboutIt)</p>
</li>


<li>_(testPreReleaseVersions)
<p>_(alphaBetaNightly)</p>
<p>_(pleaseReportIssues)</p>
</li>

<li>_(helpOtherInstantbirdUsers)
<p>_(answerQuestionsOnIRC)</p>
</li>

<li>_(translate):<p>_(translateInstantbirdIntoYourLanguage)</p></li>

<li>_(addonDevelopment):<p>_(createEditReviewAddons)</p></li>

<li>_(contributeCode):<p>_(weCanHelpYou)</p></li>
</ul>
  </div>
', $org_bundle, array('&customizeItLink;' => '<a href="https://addons.instantbird.org/">'.$org_bundle->_('customizeItLink').'</a>',
                      '&filingBugsLink;' => '<a href="https://bugzilla.instantbird.org/">'.$org_bundle->_('filingBugsLink').'</a>',
                      '&getInTouchWithUsLink;' => '<a href="http://www.instantbird.com/about.html#get_in_touch">'.$org_bundle->_('getInTouchWithUsLink').'</a>',
                      '&translateInstantbirdLink;' => '<a href="http://wiki.instantbird.org/Instantbird:Translation">'.$org_bundle->_('translateInstantbirdLink').'</a>',
                      '&createAddonLink;' => '<a href="http://wiki.instantbird.org/Instantbird:Extension">'.$org_bundle->_('createAddonLink').'</a>',
                      '&reviewAddonsLink;' => '<a href="https://addons.instantbird.org/">'.$org_bundle->_('reviewAddonsLink').'</a>',
                      '&compilingABuildYourselfLink;' => '<a href="http://wiki.instantbird.org/Instantbird:Compiling">'.$org_bundle->_('compilingABuildYourselfLink').'</a>')).
footer();
?>
