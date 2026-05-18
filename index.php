<?php
// index.php
// Main landing page to select different versions of YBS Hub

// ==========================================
// ENVIRONMENT CONFIGURATION
// ==========================================
// Set to true for Development Mode (shows the version selector page)
// Set to false for Release Mode (automatically redirects users to the latest version)
$isDevMode = true; 
// ==========================================

$baseDir = __DIR__;
$versions = [];

// Scan directory for version folders
$directories = glob($baseDir . '/Version *', GLOB_ONLYDIR);
foreach ($directories as $dir) {
    $folderName = basename($dir);
    
    // Attempt to parse version number for sorting
    preg_match('/Version ([\d\.]+)/', $folderName, $matches);
    $versionNumber = isset($matches[1]) ? floatval($matches[1]) : 0;
    
    $versions[] = [
        'name' => $folderName,
        'path' => $folderName,
        'version' => $versionNumber
    ];
}

// Sort versions descending (newest first)
usort($versions, function($a, $b) {
    return $b['version'] <=> $a['version'];
});

// Production Auto-Redirect Logic
if (!$isDevMode && !empty($versions)) {
    // The first item is the newest due to the sorting above
    $latestVersionPath = $versions[0]['path'];
    header("Location: " . $latestVersionPath . "/");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YBS Hub - Select Version</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Animated Background Gradients */
        .bg-gradient-anim {
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(56, 189, 248, 0.1) 0%, rgba(15, 23, 42, 0) 50%),
                        radial-gradient(circle at 80% 20%, rgba(234, 179, 8, 0.08) 0%, rgba(15, 23, 42, 0) 40%);
            z-index: -1;
            animation: rotateBg 30s linear infinite;
        }

        @keyframes rotateBg {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Glassmorphism Cards */
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.03) 50%, rgba(255,255,255,0) 100%);
            transform: skewX(-20deg);
            transition: all 0.7s ease;
        }

        .glass-card:hover {
            transform: translateY(-8px);
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 20px 40px -15px rgba(56, 189, 248, 0.2);
        }

        .glass-card:hover::before {
            left: 200%;
        }

        /* Subtle floating animation for the logo */
        .float-logo {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .version-badge {
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="relative flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-gradient-anim"></div>

    <div class="max-w-5xl w-full space-y-12 z-10">
        
        <!-- Header Section -->
        <div class="text-center space-y-6">
            <div class="inline-block float-logo">
                <!-- Using logo from Version 1.00 as fallback -->
                <img src="Version 1.00/assets/images/Logo/YBS_Web_Logo.svg" alt="YBS Hub Logo" class="h-28 w-auto mx-auto drop-shadow-2xl" onerror="this.src='Version 1.10/assets/images/Logo/YBS_Web_Logo.svg'">
            </div>
            <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight text-white drop-shadow-md">
                Welcome to <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-200">YBS Hub</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto font-light">
                Please select the platform version you would like to access.
            </p>
        </div>

        <!-- Versions Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-8">
            <?php if (empty($versions)): ?>
                <div class="col-span-full text-center p-8 glass-card">
                    <p class="text-slate-400">No versions found. Please ensure your directories are named "Version X.XX".</p>
                </div>
            <?php else: ?>
                <?php foreach ($versions as $index => $v): ?>
                    <?php 
                        // First item (newest version) gets a special highlight
                        $isLatest = ($index === 0);
                        $borderClass = $isLatest ? 'border-yellow-500/50 shadow-[0_0_20px_rgba(234,179,8,0.15)]' : '';
                    ?>
                    <a href="<?php echo htmlspecialchars($v['path']); ?>/" class="block group outline-none">
                        <div class="glass-card p-8 h-full flex flex-col items-center justify-center text-center space-y-4 <?php echo $borderClass; ?>">
                            
                            <?php if ($isLatest): ?>
                            <span class="absolute top-4 right-4 bg-yellow-500/20 text-yellow-400 text-xs font-bold px-3 py-1 rounded-full border border-yellow-500/30 uppercase tracking-wide">
                                Latest
                            </span>
                            <?php endif; ?>

                            <div class="h-16 w-16 rounded-2xl bg-slate-800/50 flex items-center justify-center text-3xl mb-2 group-hover:bg-sky-500/20 group-hover:text-sky-400 transition-colors duration-300 border border-slate-700">
                                <i class="fa-solid <?php echo $isLatest ? 'fa-rocket text-yellow-500 group-hover:text-yellow-400' : 'fa-code-branch text-slate-400'; ?>"></i>
                            </div>
                            
                            <h2 class="text-2xl font-bold text-white group-hover:text-sky-400 transition-colors duration-300">
                                <?php echo htmlspecialchars($v['name']); ?>
                            </h2>
                            
                            <div class="flex-grow flex items-end mt-4 w-full">
                                <span class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-slate-800/80 text-sm font-medium text-slate-300 group-hover:bg-sky-500 group-hover:text-white transition-all duration-300 border border-slate-700 group-hover:border-transparent">
                                    Launch Version <i class="fa-solid fa-arrow-right ml-2 opacity-0 group-hover:opacity-100 transform -translate-x-2 group-hover:translate-x-0 transition-all duration-300"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center pt-16">
            <p class="text-slate-500 text-sm">
                &copy; <?php echo date("Y"); ?> YBS Hub Platform. All rights reserved.
            </p>
        </div>

    </div>
</body>
</html>
