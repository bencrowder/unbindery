<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('unbindery.php');

?>

<?php include('include/header.php'); ?>

	<div id="main">
		<h2>Welcome to Unbindery</h2>

		<div class="bigcol">
			<h3>What is Unbindery?</h3>

			<p class="desc"></p>

			<h3>Current Projects</h3>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<th>Project</th>
					<th>Owner</th>
					<th>Status</th>
					<th></th>
				</tr>
				<tr>
					<td><a href="projects/aof">The Articles of Faith</a></td>
					<td>ben</td>
					<td>[coming]</td>
					<td><a href="#" class="button smallbutton">Join this project</a></td>
				</tr>
			</table>
		</div>

		<div class="sidebar">
			<h3>Stats</h3>
			<ul>
				<li>Projects: <span class="stat">3</span></li>
				<li>Items proofed this week: <span class="stat">33</span></li>
				<li>Items proofed all time: <span class="stat">1742</span></li>
			</ul>
		</div>
	</div>
</body>
</html>
