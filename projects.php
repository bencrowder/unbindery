<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Server.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

$username = Alibaba::getUsername();
$user = new User($db, $username);

?>

<?php include('include/header.php'); ?>

	<div id="main">
		<h2>Projects</h2>

		<div class="container">
			<div class="bigcol">
				<h3 class="action_header projects">Available Projects</h3>
				<ul class="action_list projects">
					<?php 
					$server = new Server($db);
					$projects = $server->getProjects();
					foreach ($projects as $project) {
						$projectlink = $SITEROOT . '/projects/' . $project["slug"];
					?>
					<li>
						<div class="percentage">
							<div class="percentage_container">
								<div class="percent" style="width: <?php echo $project["percentage"]; ?>px;"></div>
							</div> 
							<p><?php echo round($project["percentage"], 0) . "% (" . $project["completed"] . "/" . $project["total"] . ")";?></p>
						</div>
						<div class="title"><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></div>
						<div class="sub">Author: <?php echo $project["author"]; ?></div>
					</li>
					<?php } ?>
					<li></li>
				</ul>
			</div>

			<div class="sidebar leaderboard">
				<h3>Top Proofers</h3>
				<ol id="stats">
				<?php
					$users = $server->getTopUsers();
					foreach ($users as $user) { ?>
					<li><label><?php echo $user["username"]; ?></label> <span class="stat"><?php echo $user["score"]; ?></span></li>
				<?php } ?>
				</ol>
			</div>
		</div>
	</div>
</body>
</html>
