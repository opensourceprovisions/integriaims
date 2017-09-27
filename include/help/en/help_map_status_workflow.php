<h1>Estados de un ticket</h1>


<p>
A ticket may contain numerous fields. The most important of these is probably the “Status” field. 
This field marks the status when a ticket, issue or change is considered to be finished, pending on a third party, 
new or recently created, if its assigned, if it's been reopened, if it's been verified or if it's been unconfirmed. 
This cycle is open to the user and can pass on from one user to another without default restrictions. 
</p>
<p>
In case we want to define the flow, we have an option to Map statuses, 
on which we'll be able to the define which status and solutions will be available to the user: 
</p>
<p>
<?php print_image("images/help/workflow_map_status.png", false); ?>
</p>

<p>
<strong >An example of different ticket statuses</strong>
</p>
<p>
<?php print_image("images/help/workflow_map_status_2.png", false); ?>
</p>

<p>
There are particular circumstances which act automatically when whe change status. 
When changing status to “closed”, automatically a text box will be activated, an which previously wasn't accessible, 
named “epilogue”. The epilogue carries the purpose of explaining the result of the intervention or change, or which was 
-in summary- the cause of the problem and its solution. As we'll see further along, a solved ticket is the basics to generating 
an article in the knowledge base that can be revisited on other occasions, in order to solve a problem in a quick and documented manner.
</p>

