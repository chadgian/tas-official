// Create the Html5QrcodeScanner instance
// Create the Html5QrcodeScanner instance with dynamic qrbox
const scanner = new Html5QrcodeScanner("reader", {
  fps: 10,
  qrbox: function(viewfinderWidth, viewfinderHeight) {
    // Make QR box fit within the screen (80% of the smaller edge)
    let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
    return { width: minEdge * 0.8, height: minEdge * 0.8 };
  },
  supportedScanTypes: [ Html5QrcodeScanType.SCAN_TYPE_CAMERA ],
  rememberLastUsedCamera: true,
});


// Get the results element and again button
const resultsElement = document.getElementById("results");
const againButton = document.getElementById("again-btn");

// Define the onScanSuccess function
function onScanSuccess(decodedText, decodedResult) {
  // Create a new paragraph element to display the result
  // const newResult = document.createElement("p");
  // newResult.innerHTML = decodedText;

  // // Add the new result to the results element
  // resultsElement.appendChild(newResult);

  // resultsElement.innerHTML(decodedText);
  // alert(decodedText);
  var paxName;
  var paxAgency;
  if (decodedText.split("::")[0] == attendanceTrainingID){
    // console.log("ok");
    participants.forEach(participant => {
      if (participant['id'] == decodedText.split("::")[1]){
        paxName = participant['name'];
        paxAgency = participant['agency'];
      }
    });
  }

  $("#statusModal").modal("toggle");
  $("#testing").html("please wait...");
  const username = $("#username").val();

    setTimeout(() => {
      if (decodedText.split("::")[0] == attendanceTrainingID){
        $.ajax({
          type: "POST",
          url: "processAttendance.php",
          data: {
            "username": username,
            "paxID": decodedText.split("::")[1],
            "trainingID": decodedText.split("::")[0]
            },
            success: function(data) {
              if (data == "ok"){
                $("#statusModal").modal("toggle");
                $("#resultModalLabel").html("Attendance Success!")
                $("#paxName").html(paxName);
                $("#paxAgency").html(paxAgency);
                var currentDate = new Date();
                var options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'};
                var formattedDate = currentDate.toLocaleDateString('en-US', options);
                $("#scanTime").html(formattedDate);

                $("#resultModal").modal("toggle");
              } else {
                $("#statusModal").modal("toggle");
                // alert(data);
                $("#again-btn").click();
              }
            }
        }); 
      } else {
        $("#statusModal").modal("toggle");
        alert("Wrong Training!");
      }
    }, 1000);

  // Clear the scanner and show the again button
  scanner.pause();
  againButton.style.display = "block";
}

// Define the scanAgain function
function scanAgain() {
  // Scan again and hide the again button
  scanner.resume();
  againButton.style.display = "none";
}

function scrollToBottom() {
  window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
  setTimeout(() => {
    const reader = document.getElementById("reader");
    if (reader) {
      reader.scrollIntoView({ behavior: "smooth", block: "end" });
    }
  }, 120);
}

// Render the scanner
scanner.render(onScanSuccess);

// Watch for <video> element inside #reader
const readerEl = document.getElementById("reader");
if (readerEl) {
  const obs = new MutationObserver((mutations) => {
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        if (node.nodeType === 1) {
          if (node.tagName === "VIDEO" || (node.querySelector && node.querySelector("video"))) {
            console.log("Camera opened — scrolling");
            scrollToBottom();
          }
        }
      }
    }
  });

  obs.observe(readerEl, { childList: true, subtree: true });
}
