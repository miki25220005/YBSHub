<!-- Premium Admin Footer -->
<footer class="mt-auto border-t border-gray-800 bg-gray-900 text-gray-300 shadow-inner relative z-10">
    <!-- Subtle Top Glow -->
    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1/2 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
            
            <!-- Branding -->
            <div class="flex flex-col items-center md:items-start space-y-4">
                <div class="flex items-center space-x-3 group cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-800 flex items-center justify-center shadow-lg shadow-blue-900/50 group-hover:shadow-blue-500/40 transition-all duration-300">
                        <i class="fas fa-bus text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold text-white tracking-widest uppercase">YBS <span class="text-blue-500 font-light">Admin</span></span>
                </div>
                <p class="text-sm text-gray-500 text-center md:text-left max-w-xs leading-relaxed">
                    Secure administrative portal for managing the Yangon Bus Service Route Guide network.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div class="flex flex-col items-center space-y-4">
                <h3 class="text-xs font-bold tracking-[0.2em] text-gray-600 uppercase">Quick Access</h3>
                <div class="flex flex-wrap justify-center gap-x-6 gap-y-3">
                    <a href="dashboard.php" class="text-sm text-gray-400 hover:text-white transition-colors duration-300 flex items-center group">
                        <i class="fas fa-gauge-high mr-2 text-gray-600 group-hover:text-blue-400 transition-colors duration-300"></i>Dashboard
                    </a>
                    <a href="adminprofile.php" class="text-sm text-gray-400 hover:text-white transition-colors duration-300 flex items-center group">
                        <i class="fas fa-user-shield mr-2 text-gray-600 group-hover:text-blue-400 transition-colors duration-300"></i>Profile
                    </a>
                    <a href="AdManager.php" class="text-sm text-gray-400 hover:text-white transition-colors duration-300 flex items-center group">
                        <i class="fas fa-bullhorn mr-2 text-gray-600 group-hover:text-blue-400 transition-colors duration-300"></i>Ads
                    </a>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex flex-col items-center md:items-end space-y-4">
                <a href="logout.php" class="px-5 py-2.5 rounded-lg bg-gray-800/80 hover:bg-red-500/10 border border-gray-700/50 hover:border-red-500/50 text-gray-400 hover:text-red-400 transition-all duration-300 text-sm font-semibold flex items-center space-x-2 group">
                    <i class="fas fa-power-off text-gray-500 group-hover:text-red-400 transition-colors duration-300"></i>
                    <span>Secure Logout</span>
                </a>
            </div>
            
        </div>
        
        <div class="mt-10 pt-6 border-t border-gray-800/60 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <p class="text-xs text-gray-500 tracking-wide">
                &copy; <?php echo date("Y"); ?> YBS Hub Guide. All rights reserved. <span class="ml-2 px-2 py-0.5 bg-gray-800 rounded text-gray-400">Version 1.00</span>
            </p>
            <div class="flex space-x-3">
                <div class="w-8 h-8 rounded-full bg-gray-800/50 flex items-center justify-center hover:bg-gray-700 hover:text-white cursor-help transition-all duration-300 text-gray-500 text-xs shadow-inner" title="Secure Connection"><i class="fas fa-shield-alt"></i></div>
                <div class="w-8 h-8 rounded-full bg-gray-800/50 flex items-center justify-center hover:bg-gray-700 hover:text-white cursor-help transition-all duration-300 text-gray-500 text-xs shadow-inner" title="System Active"><i class="fas fa-server"></i></div>
            </div>
        </div>
    </div>
</footer>

<!-- Close any unclosed tags from the main page body -->
</body>
</html>