<!DOCTYPE html>
<html>
<head>
    <style>
        .red-text {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div id="time-display">Текущее время: <span id="time" class="red-text"></span></div>

<script>
    function updateTime() {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        document.getElementById('time').textContent = `${hours}:${minutes}:${seconds}`;
    }

    // Запускаем сразу и затем каждую секунду
    updateTime();
    setInterval(updateTime, 1000);
</script>
</body>
</html>