<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('Project.class.php');
include_once('Server.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

?>

<?php include('include/header.php'); ?>

	<div id="main">
		<h2>Home</h2>

		<div class="container">
			<div class="bigcol">
				<h3 id="current">Projects</h3>
				<ul id="current_items">
					<?php 
					$server = new Server($db);
					$projects = $server->getProjects();
					foreach ($projects as $project) {
						$projectlink = $SITEROOT . '/projects/' . $item["project_slug"];
					?>
					<li>
						<div class="proof_button"><a href="<?php echo $projectlink . '/join'; ?>" class="button">Join Project</a></div>
						<div class="item_title"><a href="<?php echo $projectlink; ?>"><?php echo $project["title"]; ?></a></div>
						<div class="project_author">Author: <?php echo $project["author"]; ?></div>
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
