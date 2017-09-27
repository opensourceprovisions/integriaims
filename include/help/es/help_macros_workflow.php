<h1>Macros</h1>

<p>
El asunto y el cuerpo del email pueden formarse mediante macros. A continuación se explican todas las macros disponibles:
</p>
<br>

<p>

<li>_incident_id_ = ID del ticket.</li>
<li>_incident_title_ = Título del ticket.</li>
<li>_creation_timestamp_ = Fecha/Hora de la creación del ticket.</li>
<li>_id_group_ = Id al Grupo asignado al ticket.</li>
<li>_name_group_ = Nombre al Grupo asignado al ticket.</li>
<li>_update_timestamp_ = La última vez que se actualizó el ticket.</li>
<li>_author_ = Creador del ticket.</li>
<li>_owner_ = Usuario que controla el ticket.</li>
<li>_id_priority_: Id de la prioridad del incidente.</li>
<li>_name_priority_: Nombre de la prioridad del incidente.</li>
<li>_access_url_ = Ruta de acceso del ticket.</li>
<li>_sitename_ = Nombre del sitio, tal y como se haya definido en el setup.</li>
<li>_fullname_ = Nombre completo del usuario que recibe el correo.</li>
<li>_username_ = Nombre del usuario que recibe el correo (login name).</li>
<li>_id_status_: Id del Estado del incidente.</li>
<li>_name_status_: Nombre del Estado del incidente.</li>
<li>_id_resolution_: Id de la resoluci&oacute;n del incidente.</li>
<li>_name_resolution_: Nombre de la resoluci&oacute;n del incidente.</li>
<li>_incident_epilog_ = Epílogo del ticket.</li>
<li>_incident_closed_by_ = Usuario que cierra el ticket.</li>
<li>_incident_own_email_: Email del usuario propietario.</li>
<li>_incident_group_email_: Email del grupo asignado.</li>
<li>_incident_auth_email_: Email del usuario creador del ticket.</li>
<li>_owner_: Usuario que gestiona el incidente.</li>
<li>_id_group_: Id del grupo asignado al incidente.</li>
<li>_name_group_: Nombre del grupo asignado al incidente.</li>
<li>_author_: Creador del incidente.</li>
<li>_type_tickets_: Tipo de tickets.</li>
<li>Plantillas de campos personalizados: Esto permite que al crear un tipo de objeto el nombre de los campos que agregas puedes incluirlos como una macro la cual mostrara el valor de dicho campo: _nombre del campo personalizado_.</li>

</p>

<p>
<b>Ejemplo de Para:</b>
<br>
_incident_owner_email_
</p>

<p>
<b>Ejemplo de Asunto:</b>
<br>
Incident #_incident_id_ _incident_title_ 
</p>

<p>
<b>Ejemplo de Cuerpo de mensaje:</b>
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



