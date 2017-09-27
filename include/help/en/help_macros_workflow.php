<h1>Macros</h1>

<p>
Following strings will be replaced on runtime on the templates who use it:
</p>
<br>

<p>

<li>_incident_id_: ID of ticket.</li>
<li>_incident_title_ = Title of ticket.</li>
<li>_creation_timestamp_ = Date/Hours Ticket Creation.</li>
<li>_id_group_: Id group assigned to this ticket.</li>
<li>_name_group_: Id group assigned to this ticket.</li>
<li>_update_timestamp_ = The last time the ticket was updated.</li>
<li>_author_: Creator of ticket.</li>
<li>_owner_: User who manages the ticket.</li>
<li>_id_priority_: Id ticket priority.</li>
<li>_name_priority_: Name ticket priority.</li>
<li>_access_url_: Incident URL.</li>
<li>_sitename_: Site name, as defined in setup.</li>
<li>_fullname_: Fullname of the user who receive the mail.</li>
<li>_username_: Name of the user who receive the mail (login name).</li>
<li>_id_status_: Id status of the ticket.</li>
<li>_name_status_: Id status of the ticket.</li>
<li>_id_resolution_: Id resolution of the ticket.</li>
<li>_name_resolution_: Name resolution of the ticket.</li>
<li>_incident_epilog_ = Epilogue ticket.</li>
<li>_incident_closed_by_ = User closes the ticket.</li>
<li>_incident_own_email_: Owner's email.</li>
<li>_incident_group_email_: Group's email.</li>
<li>_incident_auth_email_: Author's email.</li>
<li>_owner_: User who manages the incident.</li>
<li>_id_group_: Group id assigned to the incident.</li>
<li>_name_group_: Name assigned to the incident group.</li>
<li>_type_tickets_: Tickets type.
<li>Templates Custom Fields: This allows to create an object type the name of the fields you add can include them as a macro which show the value of that field, example: _Name of the custom field_.</li>

</p>

<p>
<b>Example To:</b>
<br>
_incident_own_email_
</p>

<p>
<b>Example Subject:</b>
<br>
Incident #_incident_id_ _incident_title_ 
</p>

<p>
<b>Example Text:</b>
<br>
Ticket #_incident_id_ ((_incident_title_))
<br>
   _access_url_
<br>
===================================================
<br>
    ID          : #_incident_id_ - _incident_title_
<br>
    CREATED ON  : _creation_timestamp_
<br>
    LAST UPDATE : _update_timestamp_
<br>
    GROUP       : _name_group_
<br>
    AUTHOR      : _author_
<br>
    ASSIGNED TO : _owner_
<br>
    PRIORITY    : _id_priority_
<br>
   
===================================================
<br>

_incident_main_text_
<br>
===================================================
<br>
</p>
