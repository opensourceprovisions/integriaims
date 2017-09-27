<h1>Linked fields</h1>

<p>
Son campos personalizados relacionados entre sí. Los vamos a explicar con un ejemplo: <br><br>
TIPO,VEHICULO,MARCA,MODELO,MOTOR <br><br>

Primero crearemos el campo TIPO. En este caso, no seleccionamos campo padre porque es el primero en nuestra jerarquía. Los valores irán separados por coma.
<p>
<?php print_image("images/help/linked_field1.png", false); ?>
</p>

<br><br>

Después crearemos el campo MARCA. Habrá que seleccionar el campo padre TIPO. Después rellenaremos el campo con los valores separados por coma
como en el caso anterior. <br> 
Como este campo sí tiene un padre, habrá que asociar los valores. Para ello, pondremos delante el valor del campo padre separado de |

<p>
<?php print_image("images/help/linked_field2.png", false); ?>
</p>

El siguiente campo que vamos a crear es MODELO. Se selecciona el padre MARCA y se ponen los valores.

<p>
<?php print_image("images/help/linked_field3.png", false); ?>
</p>

Por último, creamos el campo MOTOR.

<p>
<?php print_image("images/help/linked_field4.png", false); ?>
</p>

</p>
