<h1>Linked fields</h1>

<p>
Linked fields are custom fields related. We will explain with an example: <br><br>
TIPO,VEHICULO,MARCA,MODELO,MOTOR <br><br>

We create the field TIPO. In this case we don't select any parent because it's the first element in hierarchy. Values are separated by comma.
<p>
<?php print_image("images/help/linked_field1.png", false); ?>
</p>

<br><br>

We create the field MARCA. Select field TIPO as parent. Values are separated by comma. <br> 
This field has parent, so you have to associate values separated by |

<p>
<?php print_image("images/help/linked_field2.png", false); ?>
</p>

We create the field MODELO. Select field MARCA as parent and enter values.

<p>
<?php print_image("images/help/linked_field3.png", false); ?>
</p>

We create field MOTOR.

<p>
<?php print_image("images/help/linked_field4.png", false); ?>
</p>

</p>

