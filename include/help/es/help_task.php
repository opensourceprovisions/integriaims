<h1>Lista de Tareas</h1>
<p>
	Un proyecto en Integria IMS es un contenedor de tareas con fecha de inicio y fecha de fin. Los proyectos a su vez están agrupados en grupos de proyectos, por ejemplo: Proyectos de desarrollo, Proyectos facturables, Proyectos de gestión interna, etc.
	</br>
	</br>
	Internamente, un proyecto está compuesto por tareas. Estas tareas a su vez pueden contener otras tareas (de forma que se pueden definir relaciones de start-to-end entre ellas y construir una jerarquía que finalmente se verá reflejada en un diagrama gantt).
	</br>
	</br>
	Las tareas pueden (opcionalmente) contener tanto Incidencias asociadas como Worunits (unidades de trabajo) que representan una descomposición atómica de la tarea en diferentes paquetes de trabajo unipersonales (asignados a una sola persona). Esto nos permite ver de un vistazo en que se descompone una tarea, tanto en trabajo planificado (Work units) como en trabajo no planificado (incidencias).
</p>
<p>
	<?php print_image("images/help/project1.png", false, false); ?>
</p>