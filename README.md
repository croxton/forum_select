#Forum Select

* Author: [Mark Croxton](http://hallmark-design.co.uk/)

## Version 1.0.0

* Requires: ExpressionEngine 2, Discussion Forum module

## Description

Forum Select is a fieldtype that displays either a single select or multi-select menu allowing the selection of forums. The fieldtype can be customised to list forums is certain forum categories only.

## Installation

1. Copy the forum_select folder to ./system/expressionengine/third_party/
2. In the CP, navigate to Add-ons > Fieldtypes and click the 'Install' link for the Forum Select fieldtype

## Use within the {exp:channel:entries} tag

As a single tag: returns the selected forum ids delimited by |

	{my_custom_field}
	
As a tag pair: use the {forum_id} variable within your tag pair:

	{my_custom_field backspace="2"}{forum_id}, {/my_custom_field}
	
## Example: displaying topic titles for the selected forum id(s)

	{exp:channel:entries}

		{exp:forum:topic_titles 
			orderby="post_date" 
			sort="desc" 
			limit="10"
			forums="{my_custom_field}"
		}
                <tr>
                        <td><a href="{thread_path='forums/viewthread'}">{title}</a></td>
                        <td><a href="{profile_path='forums/member'}">{author}</a></td>
                        <td>{topic_date format="%m/%d/%Y %h:%i %a"}</td>
                        <td>{post_total}</td>
                        <td>{views}</td>
                        <td>On: {last_post_date format="%m/%d/%Y %h:%i %a"}<br />
                        By: <a href="{last_author_profile_path='forums/member'}">{last_author}</a></td>
                </tr>
        {/exp:forum:topic_titles}

	{/exp:channel:entries}