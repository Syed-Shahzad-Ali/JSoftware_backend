<?php if(!isset($tasks)){ ?>

<p>A new Task has been assigned to you. Login To your App to view</p>

<table border='1'>
    <tr>
        <th>Task</th>
        <th>Region</th>
        <th>City</th>
        <th>Start Date</th>
        <th>Due Date</th>
        <th>Description</th>
    </tr>
    <tr>
        <td>{{$task}}</td>
        <td>{{$region}}</td>
        <td>{{$city}}</td>
        <td>{{$startDate}}</td>
        <td>{{$dueDate}}</td>
        <td>{{$description}}</td>
    </tr>
</table>

<?php }else{ ?>

<p>You have new tasks related to snags. Login To your App to view</p>

<table border='1'>
    <tr>
        <th>Task</th>
        <th>Region</th>
        <th>City</th>
        <th>Area</th>
        <th>Start Date</th>
        <th>Due Date</th>
        <th>Description</th>
        <th>Location</th>
    </tr>

<?php foreach($tasks as $task){ ?>

    <tr>
        <td><?php echo $task['name'] ?></td>
        <td><?php echo $task['region'] ?></td>
        <td><?php echo $task['city'] ?></td>
        <td><?php echo $task['area'] ?></td>
        <td><?php echo $task['startDate'] ?></td>
        <td><?php echo $task['dueDate'] ?></td>
        <td><?php echo $task['description'] ?></td>
        <a href="http://www.google.com/maps/place/<?php echo json_decode($task['location'], true)['lat'] ?>,<?php echo json_decode($task['location'], true)['lng'] ?>">Show On Map</a>
    </tr>

<?php } ?>

 </table>

<?php } ?>