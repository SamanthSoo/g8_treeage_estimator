<?php
$connect=mysqli_connect("localhost","root","","as1") or die("Connection Failed");

//localhost - servername // root - username //""-password //
 $query="insert into student(name,marks) values('John',100)";
if(mysqli_query($connect,$query))

{
    echo "Record Inserted";

}

else

{
    echo "Record Not Inserted";
} 

 /*//Update Record
$update_query="UPDATE student SET marks = 90 WHERE name = 'John'";
if (mysqli_query($connect,$update_query))
{echo "Record Updated";}else{
    echo "Record Not Updated";
} 

//Delete Record
$delete_query="DELETE FROM student WHERE name = 'John'";
if(mysqli_query($connect,$delete_query)){
    echo "Record Deleted";
}else{
    echo "Record Not Deleted";
} */

?>