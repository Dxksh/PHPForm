<!DOCTYPE html>
<html lang='en-GB'>
  <head>
    <title>Assignment 1</title>
  </head>
  <body>
    <h1>Course Booking</h1>
    <?php
        $db_hostname = "studdb.csc.liv.ac.uk"; $db_database = "sgdsing2";
        $db_username = "sgdsing2";
        $db_password = "612021"; $db_charset = "utf8mb4";
        $dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";
        $opt = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false
        );
        try {
            $pdo = new PDO($dsn,$db_username,$db_password,$opt);

            // Variables to store user's chosen course name and time
            $selectedCourseName;
            $selectedCourseTime;


            // Displaying the courses table and availability
            echo "<h2>Courses</h2>\n";
            $stmt = $pdo->query("select * from courses order by courseName");
            echo "<table border='1'>";
            echo "<tr><th>Course Title</th><th>Day and Time</th><th>Current Capacity</th></tr>";
            while ($row = $stmt->fetch()) {
             echo "<tr>";
             echo "<td>" . $row["courseName"] . "</td>";
             echo "<td>" . $row["courseDayTime"] . "</td>";
             echo "<td>" . $row["capacity"] . "</td>";
             echo "</tr>";
             }
             echo "</table>";


            // Dropdown menu to select the course
             echo "<br><br>
             <form name='course_title' method='post'>
             <select name='course' onChange='document.course_title.submit()'>
             <option value='None'>Select a course</option>";
             $stmt = $pdo->query("select distinct courseName from courses");
             foreach($stmt as $row) {
                    $name = $row["courseName"];
                    echo "<option value='$name'>$name</option>";
                }
            echo "</select>
            </form>";
      

            // Updating value of selectedCourseName and notifying the user of their selection
            if(isset($_POST['course'])){
                $selectedCourseName = $_POST['course'];
                echo "<br>You have chosen the course: " . $selectedCourseName . "<br>";
            }

            // Dropdown menu to select the time slot
            echo "<br>
            <form name='course_time' method='post'>
            <input type='hidden' name='course' value='$selectedCourseName'>"; // Hidden input field to store selected course name
            echo "<select name='coursedaytime' onChange='document.course_time.submit()'>";
            echo "<option value=''>Select a time</option>";
                $stmt = $pdo->prepare("SELECT * FROM courses WHERE courseName = ? AND capacity > 0");
                $stmt->execute([$selectedCourseName]);
                // Time slot values are dynamically updated depending on the user's chosen course
                while ($row = $stmt->fetch()) {
                    echo "<option value='" . $row['courseDayTime'] . "'>" . $row['courseDayTime'] . " -> Slots left = " . $row['capacity'] . "</option>";
                }
            echo "</select>
            </form>";

            // Updating value of selectedCourseTime and notifying the user of their selection
            if(isset($_POST['coursedaytime'])){
                $selectedCourseTime = $_POST['coursedaytime'];
                echo "<br>You have chosen the time slot: " . $selectedCourseTime . "<br>";
            }

            // Form containing field for user details and submit button
            echo "<br>Please enter your name and phone number below" ;
            echo "
            <br>
            <form name='details' method='post'>
            <input type='hidden' name='course' value='$selectedCourseName'>
            <input type='hidden' name='coursedaytime' value='$selectedCourseTime'>
            <input type='text' name='name' placeholder='Name' required><br>
            <input type='text' name='phone' placeholder='Phone Number' required><br><br>
            <input type='submit' name='insert' value='Confirm Booking'>
            </form>";



            if (isset($_POST['insert'])) {
                
                // Validating if all fields have been filled as required
                if (!empty($selectedCourseName) && !empty($selectedCourseTime) && !empty($_POST['name']) && !empty($_POST['phone']) ) {

                    /*
                    * The functions below used to validate the user's name and phone number inputs
                    * has been sourced from: W3Schools.com (https://www.w3schools.com/php/php_regex.asp)
                    * PHP Regular Expressions
                    */

                    // Function to validate name
                    function validateName($name) {
                        // Defining pattern to validate name 
                        $pattern = "/^[a-zA-Z][a-zA-Z\s'-]*$/";

                        // Check if name matches pattern
                        if (preg_match($pattern, $name)) {
                            // Validating correct use of hyphens and apostrophes
                            if (strpos($name, "''") == false && strpos($name, '--') == false){
                                return true; 
                            }
                        }
                        return false;
                    }

                    // Function to validate phone number
                    function validatePhoneNumber($phone) {

                        $phone = str_replace(' ', '', $phone);

                        // Defining pattern to validate phone number
                        $pattern = "/^0\d{8,9}$/";

                        // Check if phone number matches pattern
                        if (preg_match($pattern, $phone)) {
                            return true; 
                        }
                        return false; 
                    }

                    // Getting name and phone number values
                    $name = $_POST['name'];
                    $phone = $_POST['phone'];

                    // Calling validation functions
                    if (validateName($name) && validatePhoneNumber($phone)) {

                        // Updating capacity of the course and slot chosen
                        $stmt = $pdo->prepare("UPDATE courses SET capacity = capacity - 1 WHERE courseName = ? AND courseDayTime = ?");
                        $stmt->execute([$selectedCourseName, $selectedCourseTime]);

                        // Creating user booking
                        $stmt = $pdo->prepare("INSERT INTO bookings (name, phoneNumber, courseTitle, courseTime) VALUES (?, ?, ?, ?)");
                        $success = $stmt->execute([$name, $phone, $selectedCourseName, $selectedCourseTime]);

                        // Prompting if booking was successful or failure
                        if ($success) {
                            echo "<p>Booking successful.</p>";

                            // Dislaying bookings table upon successful booking
                            echo "<h2>Bookings</h2>\n";
                            $stmt = $pdo->query("select * from bookings");
                            echo "<table border='1'>";
                            echo "<tr><th>Name</th><th>Phone number</th><th>Course Title</th><th>Course Time</th></tr>";
                            while ($row = $stmt->fetch()) {
                                echo "<tr>";
                                echo "<td>" . $row["name"] . "</td>";
                                echo "<td>" . $row["phoneNumber"] . "</td>";
                                echo "<td>" . $row["courseTitle"] . "</td>";
                                echo "<td>" . $row["courseTime"] . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p>Booking failed: " . $stmt->errorInfo()[2] . "</p>"; // Displaying MySQL error message
                        }
                    }
                    // Outputted if validation fails
                    else {
                        echo "Name or phone number is invalid. Please try again.";
                    }
                } 
                // Outputted if there is a missing field
                else {
                    echo "<p>Please provide name, phone number, course name and course time.</p>";
                }
            }
        }
        catch (PDOException $e) {
            exit("PDO Error: ".$e->getMessage()."<br>");
         }
