#!/usr/bin/python

import MySQLdb, re, sys
import config

marker = r"\#\#\*\*\#\#"

def dbconnect():
	try:
		conn = MySQLdb.connect(host = config.dbhost, user = config.dbusername, passwd = config.dbpassword, db = config.dbdatabase)
		return (conn, conn.cursor())
	except MySQLdb.Error, e:
		return "Error %d: %s" % (e.args[0], e.args[1])

def dbclose(conn, cursor):
	cursor.close()
	conn.commit()
	conn.close()

def get_page_ids(slug):
	(conn, db) = dbconnect()

	query = 'SELECT items.id FROM items JOIN projects ON projects.id = items.project_ID WHERE projects.slug = "%s" ORDER BY items.id' % (slug)
	db.execute(query)

	rows = db.fetchall()
	dbclose(conn, db)

	pages = []
	for row in rows:
		pages.append(row[0])

	return pages

def main():
	if (len(sys.argv) == 3):
		# Take text file and project slug as parameters
		slug = sys.argv[1]
		filename = sys.argv[2]

		# get page IDs for project slug
		page_ids = get_page_ids(slug)
		num_page_ids = len(page_ids)

		# read in the text file
		input = open(filename, 'r')
		lines = input.readlines()
		input.close()

		# split it by the marker
		page_texts = [""]
		cur = 0
		for line in lines:
			m = re.search(marker, line)
			if m:
				page_texts[cur] = page_texts[cur].strip()
				page_texts.append("")
				cur += 1
			else:
				page_texts[cur] += line

		# count number of page_ids and number of pages and die if they don't match
		num_page_texts = len(page_texts)
		if num_page_texts != num_page_ids:
			exit("Number of pages doesn't match up! %s in ID list, %s in text file" % (num_page_ids, num_page_texts))

		# connect to the database
		(conn, db) = dbconnect()

		# create the update queries as a single string
		for i in range(num_page_ids):
			query = 'UPDATE items SET transcript = "%s" WHERE id = %s;' % (conn.escape_string(page_texts[i]), page_ids[i])
			db.execute(query)

		# close the database connection
		dbclose(conn, db)
	else:
		print "Usage: unslurp project_slug textfile"


if __name__ == "__main__":
	main()
