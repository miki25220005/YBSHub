<?php
session_start();
include('../config/database.php');

if (!isset($_SESSION['AdminName'])) {
    die("Unauthorized access.");
}

if (!isset($_GET['AdID'])) {
    die("Invalid Contract ID.");
}

$adID = (int)$_GET['AdID'];
$query = "SELECT * FROM advertisements WHERE AdID = $adID";
$result = mysqli_query($connect, $query);
$ad = mysqli_fetch_assoc($result);

if (!$ad) {
    die("Contract not found.");
}

// Format dates
$startDate = date('F j, Y', strtotime($ad['StartDate']));
$endDate = date('F j, Y', strtotime($ad['EndDate']));
$createdDate = date('F j, Y', strtotime($ad['CreatedAt']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertising Contract - <?php echo htmlspecialchars($ad['CompanyName']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background: #e5e7eb; font-family: 'Times New Roman', serif; }
        .contract-page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            color: #000;
        }
        @media print {
            body { background: white; }
            .contract-page { margin: 0; box-shadow: none; border-radius: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="fixed top-4 right-4 no-print flex gap-4">
    <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-gray-700">Print</button>
    <button onclick="downloadPDF()" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Download PDF</button>
</div>

<div class="contract-page" id="contract-content">
    <div class="text-center mb-10 border-b-2 border-black pb-6">
        <h1 class="text-3xl font-bold uppercase tracking-widest">Advertising Agreement</h1>
        <p class="text-lg mt-2 text-gray-700">Yangon Bus Service Route Guide (YBS Hub)</p>
    </div>

    <div class="flex justify-between mb-8">
        <div>
            <p><strong>Contract No:</strong> YBS-AD-<?php echo str_pad($ad['AdID'], 5, '0', STR_PAD_LEFT); ?></p>
            <p><strong>Date Issued:</strong> <?php echo $createdDate; ?></p>
        </div>
        <div class="text-right">
            <p><strong>Status:</strong> <?php echo htmlspecialchars($ad['Status']); ?></p>
            <p><strong>Placement:</strong> <?php echo htmlspecialchars($ad['Placement']); ?></p>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4 border-b border-gray-400 pb-1">1. Parties to the Agreement</h2>
        <div class="pl-4">
            <p class="mb-2"><strong>The Publisher:</strong> YBS Hub Guide Administration</p>
            <p><strong>The Advertiser (Company):</strong> <?php echo htmlspecialchars($ad['CompanyName']); ?></p>
            <p><strong>Advertiser Representative:</strong> <?php echo htmlspecialchars($ad['ContactName']); ?></p>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4 border-b border-gray-400 pb-1">2. Advertisement Details</h2>
        <div class="pl-4 space-y-2">
            <p><strong>Ad Heading:</strong> <?php echo htmlspecialchars($ad['Heading']); ?></p>
            <p><strong>Target Pages:</strong> 
                <?php 
                $pages = json_decode($ad['TargetPages'], true);
                echo is_array($pages) ? implode(', ', $pages) : 'All Pages';
                ?>
            </p>
            <p><strong>Includes Link:</strong> <?php echo $ad['HasLink'] ? 'Yes (' . htmlspecialchars($ad['Link']) . ')' : 'No'; ?></p>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-xl font-bold mb-4 border-b border-gray-400 pb-1">3. Term of Agreement</h2>
        <div class="pl-4">
            <p>The Advertisement shall be displayed on the Publisher's digital property during the following period:</p>
            <ul class="list-disc pl-6 mt-2">
                <li><strong>Start Date:</strong> <?php echo $startDate; ?></li>
                <li><strong>End Date:</strong> <?php echo $endDate; ?></li>
            </ul>
            <p class="mt-4 italic text-sm text-gray-600">Note: The system will automatically remove the advertisement from the specified placement locations on the End Date at 23:59:59.</p>
        </div>
    </div>

    <div class="mb-12">
        <h2 class="text-xl font-bold mb-4 border-b border-gray-400 pb-1">4. Authorization</h2>
        <div class="pl-4">
            <p>By proceeding with this contract, the Advertiser authorizes the Publisher to display the submitted digital assets as per the details outlined above.</p>
        </div>
    </div>

    <div class="flex justify-between mt-24">
        <div class="w-1/2 pr-8">
            <div class="border-t border-black pt-2 text-center">
                <p><strong>Signature of Publisher</strong></p>
                <p>YBS Hub Admin</p>
            </div>
        </div>
        <div class="w-1/2 pl-8">
            <div class="border-t border-black pt-2 text-center">
                <p><strong>Signature of Advertiser</strong></p>
                <p><?php echo htmlspecialchars($ad['ContactName']); ?><br>(<?php echo htmlspecialchars($ad['CompanyName']); ?>)</p>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadPDF() {
        const element = document.getElementById('contract-content');
        const opt = {
            margin:       0,
            filename:     'Contract_YBS_AD_<?php echo str_pad($ad['AdID'], 5, '0', STR_PAD_LEFT); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>

</body>
</html>
