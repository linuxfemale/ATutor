<?php
/***********************************************************************/
/* ATutor															   */
/***********************************************************************/
/* Copyright (c) 2002-2009											   */
/* Adaptive Technology Resource Centre / Inclusive Design Institute	   */
/* http://atutor.ca													   */
/*																	   */
/* This program is free software. You can redistribute it and/or	   */
/* modify it under the terms of the GNU General Public License		   */
/* as published by the Free Software Foundation.					   */
/***********************************************************************/
// $Id: module_news.php 9335 2010-02-11 16:29:01Z hwong $
/*
 * Get the latest updates of this module
 * @return list of news, [timestamp]=>
 */
function faq_news() {
	global $db;
	$news = array();

	$sql = "SELECT * FROM ".TABLE_PREFIX."faq_topics T INNER JOIN ".TABLE_PREFIX."faq_entries E ON T.topic_id = E.topic_id WHERE T.course_id = $_SESSION[course_id] ORDER BY E.revised_date DESC";
	$result = mysql_query($sql, $db);
	if($result){
		while($row = mysql_fetch_assoc($result)){
			$news[] = array('time'=>$row['revised_date'], 'object'=>$row);
		}
	}
	return $news;
}

?>