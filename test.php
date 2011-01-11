<html>
<head></head>
<body>

<h3>Save Item</h3>
<form action="unbindery.php?method=save_item_text" method="POST">
Item ID: <input type="textbox" value="5" name="item_id" id="item_id" /><br/>
Project ID: <input type="textbox" value="3" name="project_id" id="project_id" /><br/>
Username: <input type="textbox" value="" name="username" id="username" /><br/>
Draft: <input type="checkbox" name="draft" id="draft" /><br/>
Itemtext: <textarea name="itemtext" id="itemtext"></textarea><br/>
<br/>
<input type="submit" value="Submit" />
</form>

<h3>Get Item</h3>
<form action="unbindery.php?method=get_item" method="POST">
Item ID: <input type="textbox" value="5" name="item_id" id="item_id" /><br/>
Project slug: <input type="textbox" value="aof" name="project_slug" id="project_slug" /><br/>
Username: <input type="textbox" value="" name="username" id="username" /><br/>
<br/>
<input type="submit" value="Submit" />
</form>

<h3>Get Project</h3>
<form action="unbindery.php?method=get_project" method="POST">
Project slug: <input type="textbox" value="aof" name="slug" id="slug" /><br/>
<br/>
<input type="submit" value="Submit" />
</form>

<h3>Get User Assignments</h3>
<form action="unbindery.php?method=get_user_assignments" method="POST">
Username: <input type="textbox" value="" name="username" id="username" /><br/>
<br/>
<input type="submit" value="Submit" />
</form>

<h3>Get User Projects</h3>
<form action="unbindery.php?method=get_user_projects" method="POST">
Username: <input type="textbox" value="" name="username" id="username" /><br/>
<br/>
<input type="submit" value="Submit" />
</form>

<h3>Assign User to Project</h3>
<form action="unbindery.php?method=assign_user_to_project" method="POST">
Username: <input type="textbox" value="" name="username" id="username" /><br/>
Project Slug: <input type="textbox" value="" name="project_slug" id="project_slug" /><br/>
<br/>
<input type="submit" value="Submit" />
</form>

<h3>Assign Item to User</h3>
<form action="unbindery.php?method=assign_item_to_user" method="POST">
Username: <input type="textbox" value="" name="username" id="username" /><br/>
Item ID: <input type="textbox" value="" name="item_id" id="item_id" /><br/>
Project Slug: <input type="textbox" value="" name="project_slug" id="project_slug" /><br/>
<br/>
<input type="submit" value="Submit" />
</form>

</body>
</html>
