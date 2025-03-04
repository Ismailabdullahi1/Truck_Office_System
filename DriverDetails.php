<?php
include('db.php');

if (isset($_GET['driver_id'])) {
    $driver_id = $_GET['driver_id'];

    // Query for trips, including paid and unpaid trips
    $query = "SELECT trip_no, start_location, start_date, end_location, end_date, rate, fuel, disparage, expenses, net_income, paid
              FROM trips
              WHERE driver_id = $driver_id
              ORDER BY start_date ASC";

    $result = $conn->query($query);
    $driverQuery = "SELECT full_name FROM drivers WHERE driver_id = $driver_id";
    $driverResult = $conn->query($driverQuery);
    $driver = $driverResult->fetch_assoc();
} else {
    echo "No driver specified.";
    exit;
}

// Calculate Total Net Income
$totalNetIncome = 0;
$totalPaid = 0;
while ($row = $result->fetch_assoc()) {
    $totalNetIncome += $row['net_income'];
    if ($row['paid'] == 1) {
        $totalPaid += $row['net_income']; // Add to total paid if the trip is marked as paid
    }
}
// Reset result pointer for table display
$result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Report</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .company-logo {
            max-width: 150px;
        }
        .header-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 30px;
            font-weight: bold;
        }
        .report-container {
            margin-top: 30px;
        }
        .print-btn {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        .print-btn:hover {
            background-color: #218838;
        }
        .table th, .table td {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
        }
        .back-btn {
            margin-bottom: 20px;
        }
        
    </style>
    <script>
    function printReport() {
        var printWindow = window.open('', '', 'height=800,width=800');
        
        // Get current date and time
        var currentDateTime = new Date();
        var dateTimeString = currentDateTime.toLocaleString(); // Format the date and time as needed

        printWindow.document.write('<html><head><title>Official Report</title><style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
        printWindow.document.write('.company-logo { max-width: 150px; }');
        printWindow.document.write('.table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
        printWindow.document.write('.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('.table th { background-color: #f2f2f2; }');
        printWindow.document.write('</style></head><body>');
        
        printWindow.document.write('<img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" class="company-logo" alt="Company Logo">');
        printWindow.document.write('<h2> Official ' + document.getElementById("driver-name").innerText + '</h2>');
        
        // Add current date and time
        printWindow.document.write('<p>Report Generated on: ' + dateTimeString + '</p>');
        
        printWindow.document.write(document.getElementById("report-table").outerHTML);
        printWindow.document.write('<p>This is Computer Generated official Report </p>');
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.print();
    }
</script>

</head>
<body>


<!-- Header with Company Logo and Name -->
<div class="header-container">
    <img src="images/Screenshot_2024-12-22_164605-removebg-preview.png" class="company-logo" alt="Company Logo">
    
</div>
<div class="container back-btn">
    <a href="javascript:history.back()" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>
<div class="container report-container">
    <h2 id="driver-name">Driver Report: <?php echo $driver['full_name']; ?></h2>
    
    <!-- Print Button -->
    <button class="print-btn" onclick="printReport()">Print Report</button>

    <table class="table table-bordered" id="report-table">
        <thead>
            <tr>
                <th>Trip No</th>
                <th>Start Location</th>
                <th>Start Date</th>
                <th>End Location</th>
                <th>End Date</th>
                <th>Rate</th>
                <th>Fuel</th>
                <th>Disparage</th>
                <th>Expenses</th>
                <th>Net Total</th>
                <th>Paid</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['trip_no']; ?></td>
                    <td><?php echo $row['start_location']; ?></td>
                    <td><?php echo $row['start_date']; ?></td>
                    <td><?php echo $row['end_location']; ?></td>
                    <td><?php echo $row['end_date']; ?></td>
                    <td>$<?php echo number_format($row['rate'], 2); ?></td>
                    <td>$<?php echo number_format($row['fuel'], 2); ?></td>
                    <td>$<?php echo number_format($row['disparage'], 2); ?></td>
                    <td>$<?php echo number_format($row['expenses'], 2); ?></td>
                    <td>$<?php echo number_format($row['net_income'], 2); ?></td>
                    <td><?php echo $row['paid'] ? 'Yes' : 'No'; ?></td>
                </tr>
            <?php } ?>
            <!-- Total Net Income Row -->
            <tr class="total-row">
                <td colspan="9" class="text-right">Total Net Income</td>
                <td>$<?php echo number_format($totalNetIncome, 2); ?></td>
                <td></td>
            </tr>
             <!-- Total Paid Row -->
             <tr class="total-row">
                <td colspan="9" class="text-right">Total Paid</td>
                <td>$<?php echo number_format($totalPaid, 2); ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>

</body>
</html>
