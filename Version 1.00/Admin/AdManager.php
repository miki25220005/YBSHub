<?php
session_start();
include('../config/database.php');
include('../includes/admheader.php');

if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Handle Form Submission (Add Ad)
if (isset($_POST['btnSave'])) {
    $companyName = mysqli_real_escape_string($connect, $_POST['txtCompany']);
    $contactName = mysqli_real_escape_string($connect, $_POST['txtContact']);
    $placement = mysqli_real_escape_string($connect, $_POST['cboPlacement']);
    $targetPages = isset($_POST['chkPages']) ? json_encode($_POST['chkPages']) : '["All"]';
    
    $heading = mysqli_real_escape_string($connect, $_POST['txtHeading']);
    $description = mysqli_real_escape_string($connect, $_POST['txtDescription']);
    $hasLink = isset($_POST['chkHasLink']) ? 1 : 0;
    $link = $hasLink ? mysqli_real_escape_string($connect, $_POST['txtLink']) : '';
    $startDate = mysqli_real_escape_string($connect, $_POST['txtStartDate']);
    $endDate = mysqli_real_escape_string($connect, $_POST['txtEndDate']);
    $status = mysqli_real_escape_string($connect, $_POST['cboStatus']);

    // Handle Image Upload
    $imagePath = '';
    if (isset($_FILES['fileImage']['name']) && $_FILES['fileImage']['name'] != '') {
        $imgName = time() . '_' . $_FILES['fileImage']['name'];
        $imgPath = '../Ads/images/' . $imgName;
        move_uploaded_file($_FILES['fileImage']['tmp_name'], $imgPath);
        $imagePath = 'Ads/images/' . $imgName;
    }

    $insert = "INSERT INTO advertisements (CompanyName, ContactName, Placement, TargetPages, Image, Heading, Description, HasLink, Link, StartDate, EndDate, Status) 
               VALUES ('$companyName', '$contactName', '$placement', '$targetPages', '$imagePath', '$heading', '$description', $hasLink, '$link', '$startDate', '$endDate', '$status')";
    
    if (mysqli_query($connect, $insert)) {
        echo "<script>alert('Ad successfully created!'); window.location='AdManager.php';</script>";
    } else {
        echo "<script>alert('Error creating Ad.');</script>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($connect, "DELETE FROM advertisements WHERE AdID=$id");
    echo "<script>alert('Ad deleted!'); window.location='AdManager.php';</script>";
}

// Fetch all ads
$adsQuery = "SELECT * FROM advertisements ORDER BY AdID DESC";
$adsResult = mysqli_query($connect, $adsQuery);
?>

<div class="pt-28 pb-12 flex flex-col min-h-screen text-gray-800">

<div class="container mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Ad Management System</h1>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow transition duration-200">
            <i class="fas fa-plus mr-2"></i> Create New Ad
        </button>
    </div>

    <!-- Active Ads Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Company</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Placement</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Date Range</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($row = mysqli_fetch_assoc($adsResult)): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-bold text-gray-900"><?php echo htmlspecialchars($row['CompanyName']); ?></div>
                        <div class="text-sm text-gray-500">Contact: <?php echo htmlspecialchars($row['ContactName']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($row['Placement']); ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <?php echo $row['StartDate'] . ' to ' . $row['EndDate']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($row['Status'] == 'Active' && strtotime($row['EndDate']) >= strtotime(date('Y-m-d'))): ?>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">Active</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">Expired/Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-3">
                        <a href="../Ads/GenerateContract.php?AdID=<?php echo $row['AdID']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Generate Contract">
                            <i class="fas fa-file-contract fa-lg"></i>
                        </a>
                        <a href="AdManager.php?delete=<?php echo $row['AdID']; ?>" onclick="return confirm('Delete this ad?');" class="text-red-600 hover:text-red-900" title="Delete Ad">
                            <i class="fas fa-trash fa-lg"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Ad Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl transform transition-all p-6 relative">
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-3">Create New Advertisement</h2>
            
            <form action="AdManager.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Company Details -->
                    <div>
                        <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-building mr-2"></i>Contract Details</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Company Name</label>
                                <input type="text" name="txtCompany" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact / Username</label>
                                <input type="text" name="txtContact" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" name="txtStartDate" required value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                                </div>
                                <div class="w-1/2">
                                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" name="txtEndDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ad Placement & Targeting -->
                    <div>
                        <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-crosshairs mr-2"></i>Placement & Targeting</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ad Placement</label>
                                <select name="cboPlacement" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                                    <option value="Popup">Popup Modal</option>
                                    <option value="TopBanner">Top Banner (Future)</option>
                                    <option value="BottomBanner">Bottom Banner (Future)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Target Pages</label>
                                <div class="space-y-2 bg-gray-50 p-3 rounded-lg border">
                                    <label class="flex items-center"><input type="checkbox" name="chkPages[]" value="All" checked class="mr-2"> Every Page</label>
                                    <label class="flex items-center"><input type="checkbox" name="chkPages[]" value="index.php" class="mr-2"> Home Page</label>
                                    <label class="flex items-center"><input type="checkbox" name="chkPages[]" value="BusList.php" class="mr-2"> Bus List</label>
                                    <label class="flex items-center"><input type="checkbox" name="chkPages[]" value="GateList.php" class="mr-2"> Gate List</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Ad Content -->
                <div>
                    <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-image mr-2"></i>Advertisement Content</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ad Heading</label>
                                <input type="text" name="txtHeading" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ad Image</label>
                                <input type="file" name="fileImage" accept="image/*" required class="mt-1 block w-full border-gray-300 shadow-sm p-1 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="cboStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ad Description / Text</label>
                                <textarea name="txtDescription" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                <label class="flex items-center font-bold text-blue-800 cursor-pointer">
                                    <input type="checkbox" name="chkHasLink" id="chkHasLink" class="mr-2 h-5 w-5 text-blue-600 rounded">
                                    Include Clickable Link?
                                </label>
                                <div id="linkContainer" class="mt-3 hidden">
                                    <label class="block text-sm font-medium text-blue-800">Redirect URL</label>
                                    <input type="url" name="txtLink" placeholder="https://..." class="mt-1 block w-full rounded-md border-blue-200 shadow-sm p-2 border focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-bold transition">Cancel</button>
                    <button type="submit" name="btnSave" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md transition">Create Ad & Contract</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle Link Input
    document.getElementById('chkHasLink').addEventListener('change', function() {
        document.getElementById('linkContainer').classList.toggle('hidden', !this.checked);
        if (this.checked) {
            document.querySelector('input[name="txtLink"]').setAttribute('required', 'required');
        } else {
            document.querySelector('input[name="txtLink"]').removeAttribute('required');
        }
    });
</script>

<?php include('../includes/admfooter.php'); ?>
</div>
