<h1>Estados de un ticket</h1>


<p>
Un ticket tiene numerosos campos. Quizás el más importante es el campo estado. 
Este campo se refiere a si un ticket / problema / cambio se da por cerrado, 
pendiente de un tercero, nuevo o recién creado, si está asignado, si se ha reabierto, 
si se ha verificado o si no se ha confirmado. Este ciclo está abierto al usuario y se puede pasar de uno a otro sin restricción por defecto. 
</p>
<p>
En caso de querer definir el flujo tenemos la opción del Mapeo de estados, 
en la cual podremos definir que estados y resoluciones estarán disponibles para el usuario: 
</p>
<p>
<?php print_image("images/help/workflow_map_status.png", false); ?>
</p>

<p>
<strong >Un Ejemplo de diferentes estados de un ticket</strong>
</p>
<p>
<?php print_image("images/help/workflow_map_status_2.png", false); ?>
</p>

<p>
Existen determinadas circunstancias que actúan automáticamente cuando pasamos de un estado a otro. 
Al pasar de cualquier estado, al estado «cerrado», automáticamente se activará una casilla de texto que antes no estaba accesible 
llamada «epílogo» que sirve para explicar cuál fué el resultado de la intervención o cambio o cual fué -en suma- la causa del problema 
y su solución. Como se verá más adelante, un ticket solucionado es la base para generar un artículo en la base de conocimiento que 
sirva para en posteriores ocasiones, solucionar un problema de forma rápida y documentada. 
</p>
