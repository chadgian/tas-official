<?php
include '../processes/db_connection.php';

// Define the column name
// $column = "trainingID";
$trainingID = $_SESSION['training'];

// Prepare the first query
// $stmt = $conn->prepare("SELECT * FROM `_self-attendance-details` WHERE trainingID = ?");
// $stmt->bind_param("s", $trainingID);
// $stmt->execute();

// // Get the result and extract the training ID
// $result = $stmt->get_result();
// $data = $result->fetch_assoc();

// Define the attendance table name
$attendanceTable = "training-$trainingID-1";

// Prepare the second query
$stmt = $conn->prepare("SELECT * FROM `$attendanceTable`");
$stmt->execute();
$result = $stmt->get_result();

// Initialize an empty array to store participants
$participants = [];

// Fetch and process the participant data
while ($row = $result->fetch_assoc()) {
  $participant = [
    "id" => $row['participant_id'],
    "name" => trim("{$row['firstname']} {$row['middle_initial']} {$row['lastname']}"),
    "agency" => $row['agency']
  ];
  $participants[] = $participant;
}

// $selfAttendanceDetails = $conn->prepare("SELECT * FROM `_self-attendance-details`");
// $selfAttendanceDetails->execute();
// $selfAttendanceResult = $selfAttendanceDetails->get_result();

// $selfAttendance = [];

// while ($selfAttendanceData = $selfAttendanceResult->fetch_assoc()) {
//   $selfAttendance[$selfAttendanceData['detailName']] = $selfAttendanceData['detailData'];
// }

// // Output the participant names
// foreach ($participants as $participant) {
//   echo "Name: " . $participant['name'] . "<br>"; // added <br> for better output
// }
?>

<div style="width: 500px" id="reader"></div>
<input type="hidden" id="username" name="username" value="<?php echo $_SESSION['username']; ?>">
<button onclick="scanAgain()" id="again-btn" style="display: none;" class="btn btn-warning">Scan again</button>
<div id="results">

</div>

<div class="modal fade" id="statusModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog d-flex justify-content-center align-items-center modal-dialog-centered">
    <div class="loading">
      <img src="img/loading.gif" alt="">
    </div>
  </div>
</div>

<div class="modal fade" id="resultModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="resultModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="resultModalLabel">Modal title</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
          onclick="scanAgain()"></button>
      </div>
      <div class="modal-body bg-white d-flex flex-column justify-content-center align-items-center">
        <div id="paxName" class="h2 mb-3">

        </div>
        <img src="img/checked.png" alt="" width="25%">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="scanAgain()">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  crossorigin="anonymous"></script>

<script>
  const attendanceTrainingID = <?php echo $trainingID999; ?>;

  const participants = <?php echo json_encode($participants); ?>;
  const trainingID = "<?php echo $selfAttendance['trainingID']; ?>";
  //  console.log("List of participants: ");
  //  participants.forEach(participant => {
  //    console.log(participant['id'] + ": " + participant['name']);
  //  });
</script>
<script src="html5-qrcode.min.js"></script>
<script src="script.js"></script>