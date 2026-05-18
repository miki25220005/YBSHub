<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to DB if not already connected
if (!isset($connect)) {
    include_once('../config/database.php');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$today = date('Y-m-d');

// Fetch all active ads
$query = "SELECT * FROM advertisements 
          WHERE Status = 'Active' 
          AND StartDate <= '$today' 
          AND EndDate >= '$today' 
          ORDER BY AdID DESC";

$result = mysqli_query($connect, $query);

$adsToShow = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($adData = mysqli_fetch_assoc($result)) {
        $targetPages = json_decode($adData['TargetPages'], true);
        
        // Check if the ad targets this page or 'All'
        $isTargeted = false;
        if (is_array($targetPages)) {
            if (in_array('All', $targetPages) || in_array($currentPage, $targetPages)) {
                $isTargeted = true;
            }
        }
        
        if ($isTargeted) {
            $sessionKey = 'ad_shown_' . $adData['AdID'];
            
            if ($adData['Placement'] == 'Popup') {
                // Popups show once per session
                if (!isset($_SESSION[$sessionKey])) {
                    $_SESSION[$sessionKey] = true;
                    $adsToShow[] = $adData;
                }
            } else {
                // Banners show always (managed by sessionStorage in JS for closing)
                $adsToShow[] = $adData;
            }
        }
    }
}
?>

<?php if (count($adsToShow) > 0): ?>
    <style>
        /* Popup Styles */
        .ybs-popup-ad { 
            position: fixed; 
            inset: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            background-color: rgba(0, 0, 0, 0.4); 
            backdrop-filter: blur(4px);
            z-index: 9999; 
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .ybs-popup-ad.show {
            opacity: 1;
            visibility: visible;
        }
        .ybs-popup-content {
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .ybs-popup-ad.show .ybs-popup-content {
            transform: scale(1);
            opacity: 1;
        }

        /* Banner Styles */
        .ybs-banner-ad {
            position: fixed;
            left: 0;
            right: 0;
            z-index: 9998;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.4s ease-in-out;
        }
        .ybs-banner-top { top: 0; transform: translateY(-100%); }
        .ybs-banner-bottom { bottom: 0; transform: translateY(100%); }
        
        .ybs-banner-ad.show { transform: translateY(0); }
    </style>

    <?php foreach ($adsToShow as $ad): ?>
        <?php if ($ad['Placement'] == 'Popup'): ?>
            <!-- Popup Ad -->
            <div id="ad-<?php echo $ad['AdID']; ?>" class="ybs-popup-ad" data-type="Popup">
                <div class="ybs-popup-content relative bg-white/95 backdrop-blur-sm p-6 w-96 max-w-full rounded-2xl shadow-2xl text-center border border-white/50">
                    <button class="close-ad-btn absolute -top-4 -right-4 text-gray-600 hover:text-white text-2xl w-10 h-10 flex items-center justify-center rounded-full bg-white hover:bg-red-500 transition duration-300 shadow-lg border border-gray-100" data-target="ad-<?php echo $ad['AdID']; ?>">&times;</button>
                    <?php if (!empty($ad['Image'])): ?>
                        <img src="<?php echo htmlspecialchars($ad['Image']); ?>" alt="Advertisement" class="w-full h-48 object-cover rounded-xl shadow-inner mb-4">
                    <?php endif; ?>
                    <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight leading-tight"><?php echo htmlspecialchars($ad['Heading']); ?></h2>
                    <p class="text-gray-600 text-sm mt-3 px-2 leading-relaxed"><?php echo nl2br(htmlspecialchars($ad['Description'])); ?></p>
                    <?php if ($ad['HasLink'] && !empty($ad['Link'])): ?>
                        <a href="<?php echo htmlspecialchars($ad['Link']); ?>" target="_blank" class="block w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3.5 px-6 rounded-xl mt-6 text-base shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5">Claim Offer 🚀</a>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($ad['Placement'] == 'TopBanner' || $ad['Placement'] == 'BottomBanner'): ?>
            <!-- Banner Ad -->
            <div id="ad-<?php echo $ad['AdID']; ?>" class="ybs-banner-ad <?php echo $ad['Placement'] == 'TopBanner' ? 'ybs-banner-top mt-16' : 'ybs-banner-bottom'; ?>" data-type="Banner" data-id="<?php echo $ad['AdID']; ?>">
                <div class="flex items-center space-x-4 flex-1">
                    <?php if (!empty($ad['Image'])): ?>
                        <img src="<?php echo htmlspecialchars($ad['Image']); ?>" alt="Advertisement" class="h-12 w-12 object-cover rounded-md shadow-sm">
                    <?php endif; ?>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm md:text-base leading-tight"><?php echo htmlspecialchars($ad['Heading']); ?></h3>
                        <p class="text-xs md:text-sm text-gray-600 hidden md:block"><?php echo htmlspecialchars(substr($ad['Description'], 0, 80)) . (strlen($ad['Description']) > 80 ? '...' : ''); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($ad['HasLink'] && !empty($ad['Link'])): ?>
                        <a href="<?php echo htmlspecialchars($ad['Link']); ?>" target="_blank" class="whitespace-nowrap bg-blue-600 hover:bg-blue-700 text-white text-xs md:text-sm font-bold py-2 px-4 rounded-lg shadow transition">View</a>
                    <?php endif; ?>
                    <button class="close-ad-btn text-gray-400 hover:text-red-500 transition text-xl px-2" data-target="ad-<?php echo $ad['AdID']; ?>">&times;</button>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const allAds = document.querySelectorAll('.ybs-popup-ad, .ybs-banner-ad');
            const closeBtns = document.querySelectorAll('.close-ad-btn');

            // Show ads with a slight delay
            setTimeout(() => {
                allAds.forEach(ad => {
                    const type = ad.getAttribute('data-type');
                    const adId = ad.getAttribute('data-id');
                    
                    // For banners, check if user already closed it in this session
                    if (type === 'Banner') {
                        if (!sessionStorage.getItem('closed_banner_' + adId)) {
                            ad.classList.add("show");
                        }
                    } else {
                        // Popups show if they rendered
                        ad.classList.add("show");
                    }
                });
            }, 1500);

            // Handle close buttons
            closeBtns.forEach(btn => {
                btn.addEventListener("click", function () {
                    const targetId = this.getAttribute('data-target');
                    const adElement = document.getElementById(targetId);
                    if (adElement) {
                        adElement.classList.remove("show");
                        
                        // If it's a banner, remember it's closed for this browser session
                        if (adElement.getAttribute('data-type') === 'Banner') {
                            const adId = adElement.getAttribute('data-id');
                            sessionStorage.setItem('closed_banner_' + adId, 'true');
                        }
                    }
                });
            });
        });
    </script>
<?php endif; ?>