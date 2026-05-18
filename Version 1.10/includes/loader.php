<?php
/**
 * Lightweight loader include for public pages.
 * - Avoids nested <html>/<head> issues from including loader.html.
 * - Uses sessionStorage to prevent quick "double loader" flashes (e.g. Destination search submit).
 */
?>

<style>
    /* Loading Screen Container */
    #loading-screen {
        position: fixed;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: white;
        z-index: 9999;
    }

    #loading-screen.hide {
        opacity: 0;
        transition: opacity 0.5s ease-out;
        pointer-events: none;
    }

    /* Loader Text */
    .loader-text {
        width: fit-content;
        font-weight: bold;
        font-family: Helvetica, Arial, sans-serif;
        white-space: pre;
        font-size: 24px;
        line-height: 2em;
        height: 1.8em;
        overflow: hidden;
        margin-top: 10px;
    }

    .loader-text:before {
        content: "Loading...\Aဖွင့်နေသည်...\A로딩 중...\Aロード中...\A載入中...";
        white-space: pre;
        display: inline-block;
        animation: loadingText 2.5s infinite steps(5) alternate;
    }

    @keyframes loadingText {
        100% { transform: translateY(-100%); }
    }

    .loader {
        width: fit-content;
        height: fit-content;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .busWrapper {
        width: 200px;
        height: 100px;
        display: flex;
        flex-direction: column;
        position: relative;
        align-items: center;
        justify-content: flex-end;
        overflow-x: hidden;
    }

    .busBody {
        width: 150px;
        height: fit-content;
        margin-bottom: 6px;
        animation: motion 1s linear infinite;
    }

    @keyframes motion {
        0% { transform: translateY(0px); }
        50% { transform: translateY(3px); }
        100% { transform: translateY(0px); }
    }

    .busTires {
        width: 130px;
        height: fit-content;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0px 10px 0px 15px;
        position: absolute;
        bottom: 0;
    }

    .busTires svg {
        width: 24px;
        height: 24px;
        display: block;
    }

    .road {
        width: 100%;
        height: 1.5px;
        background-color: #282828;
        position: relative;
        bottom: 0;
        align-self: flex-end;
        border-radius: 3px;
    }

    .road::before {
        content: "";
        position: absolute;
        width: 20px;
        height: 100%;
        background-color: #282828;
        right: -50%;
        border-radius: 3px;
        animation: roadAnimation 1.4s linear infinite;
        border-left: 10px solid white;
    }

    .road::after {
        content: "";
        position: absolute;
        width: 10px;
        height: 100%;
        background-color: #282828;
        right: -65%;
        border-radius: 3px;
        animation: roadAnimation 1.4s linear infinite;
        border-left: 4px solid white;
    }

    .lampPost {
        position: absolute;
        bottom: 0;
        right: -90%;
        height: 90px;
        animation: roadAnimation 1.4s linear infinite;
    }

    @keyframes roadAnimation {
        0% { transform: translateX(0px); }
        100% { transform: translateX(-350px); }
    }
</style>

<div id="loading-screen" aria-live="polite" aria-busy="true">
    <div class="loader">
        <div class="busWrapper">
            <div class="busBody">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 198 93" class="bussvg">
                    <rect stroke-width="3" stroke="#282828" fill="#FFFF00" rx="5" height="60" width="150" y="15" x="25"></rect>
                    <text x="95" y="70" font-family="monospace" font-size="20px" font-weight="bold" fill="#000000" text-anchor="middle">YBS</text>
                    <rect stroke-width="2" stroke="#282828" fill="#87CEEB" rx="2" height="30" width="15" y="30" x="160"></rect>
                    <rect stroke-width="2" stroke="#282828" fill="#87CEEB" rx="2" height="15" width="20" y="35" x="120"></rect>
                    <rect stroke-width="2" stroke="#282828" fill="#87CEEB" rx="2" height="15" width="20" y="35" x="90"></rect>
                    <rect stroke-width="2" stroke="#282828" fill="#87CEEB" rx="2" height="15" width="20" y="35" x="60"></rect>
                    <rect stroke-width="2" stroke="#282828" fill="#D3D3D3" rx="2" height="30" width="10" y="30" x="30"></rect>
                </svg>
            </div>
            <div class="busTires">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 30 30" width="24" height="24" style="display:block" class="tiresvg">
                    <circle stroke-width="3" stroke="#282828" fill="#282828" r="13.5" cy="15" cx="15"></circle>
                    <circle fill="#DFDFDF" r="7" cy="15" cx="15"></circle>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 30 30" width="24" height="24" style="display:block" class="tiresvg">
                    <circle stroke-width="3" stroke="#282828" fill="#282828" r="13.5" cy="15" cx="15"></circle>
                    <circle fill="#DFDFDF" r="7" cy="15" cx="15"></circle>
                </svg>
            </div>
            <div class="road"></div>
            <svg xml:space="preserve" viewBox="0 0 453.459 453.459" xmlns="http://www.w3.org/2000/svg" fill="#000000" class="lampPost" aria-hidden="true">
                <path d="M252.882,0c-37.781,0-68.686,29.953-70.245,67.358h-6.917v8.954c-26.109,2.163-45.463,10.011-45.463,19.366h9.993
                c-1.65,5.146-2.507,10.54-2.507,16.017c0,28.956,23.558,52.514,52.514,52.514c28.956,0,52.514-23.558,52.514-52.514
                c0-5.478-0.856-10.872-2.506-16.017h9.992c0-9.354-19.352-17.204-45.463-19.366v-8.954h-6.149C200.189,38.779,223.924,16,252.882,16
                c29.952,0,54.32,24.368,54.32,54.32c0,28.774-11.078,37.009-25.105,47.437c-17.444,12.968-37.216,27.667-37.216,78.884v113.914
                h-0.797c-5.068,0-9.174,4.108-9.174,9.177c0,2.844,1.293,5.383,3.321,7.066c-3.432,27.933-26.851,95.744-8.226,115.459v11.202h45.75
                v-11.202c18.625-19.715-4.794-87.527-8.227-115.459c2.029-1.683,3.322-4.223,3.322-7.066c0-5.068-4.107-9.177-9.176-9.177h-0.795
                V196.641c0-43.174,14.942-54.283,30.762-66.043c14.793-10.997,31.559-23.461,31.559-60.277C323.202,31.545,291.656,0,252.882,0z"></path>
            </svg>
        </div>
    </div>
    <div class="loader-text"></div>
    <p style="margin-top: 6px; font-size: 20px; font-weight: 700; color: #1f2937;"><b>YBS Hub</b></p>
</div>

<script>
    (function () {
        // Prevent rapid double-flash: if this page was just loaded, skip showing loader.
        const key = 'ybs_loader_last';
        const now = Date.now();
        const lastRaw = sessionStorage.getItem(key);
        const last = lastRaw ? parseInt(lastRaw, 10) : 0;
        sessionStorage.setItem(key, String(now));

        const screen = document.getElementById('loading-screen');
        if (!screen) return;

        // If navigations happen quickly (e.g. Destination search submit), don't show loader again.
        if (last && (now - last) < 1200) {
            screen.style.display = 'none';
            return;
        }

        const hide = () => {
            screen.classList.add('hide');
            window.setTimeout(() => {
                screen.style.display = 'none';
            }, 550);
        };

        window.addEventListener('load', () => {
            // Small delay so it feels smooth, but not always 1.5s.
            window.setTimeout(hide, 250);
        });
    })();
</script>

