<?php
session_start();
require_once '../db_connect.php'; // Sesuaikan path jika diperlukan

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../accounts/login.php');
    exit();
}

// Fungsi untuk mengambil data pengguna
function getUserData($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil data pengguna
$userData = getUserData($pdo, $_SESSION['user_id']);

// Fungsi untuk memperbarui saldo pengguna
function updateBalance($pdo, $userId, $amount)
{
    $stmt = $pdo->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realistic Plinko Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.18.0/matter.min.js"></script>
    <style>
        #plinko-canvas {
            border: 2px solid #4a5568;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>

<body class="bg-gray-900 text-white">

    <body class="min-h-screen">
        <?php include '../includes/navbar.php'; ?>

        <div class="container mx-auto px-4 py-8">
            <h1 class="text-4xl font-bold mb-8 text-center text-white">Realistic Plinko Game</h1>

            <div class="max-w-md mx-auto glassmorphism p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-4">Your Balance: $<span id="userBalance"><?php echo number_format($userData['balance'], 2); ?></span></h2>

                <form id="plinkoForm" class="mb-4">
                    <div class="mb-4">
                        <label for="bet_amount" class="block mb-2">Bet Amount:</label>
                        <input type="number" id="bet_amount" name="bet_amount" min="1" max="<?php echo $userData['balance']; ?>" step="0.01" required class="w-full px-3 py-2 bg-gray-700 rounded-md">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Spawn Ball
                    </button>
                </form>
            </div>

            <div id="gameResult" class="max-w-md mx-auto glassmorphism p-6 mb-8" style="display: none;">
                <h2 class="text-2xl font-semibold mb-4">Game Result</h2>
                <p>Ball landed in bucket: <span id="resultBucket"></span></p>
                <p>Multiplier: <span id="resultMultiplier"></span>x</p>
                <p>Win Amount: $<span id="resultWinAmount"></span></p>
                <p>Profit/Loss: $<span id="resultProfitLoss"></span></p>
            </div>

            <canvas id="plinko-canvas" width="400" height="600" class="glassmorphism"></canvas>
        </div>

        <script>
            // Matter.js module aliases
            var Engine = Matter.Engine,
                Render = Matter.Render,
                Runner = Matter.Runner,
                Bodies = Matter.Bodies,
                Composite = Matter.Composite;

            // Create engine
            var engine = Engine.create();

            // Create renderer
            var render = Render.create({
                element: document.body,
                engine: engine,
                canvas: document.getElementById('plinko-canvas'),
                options: {
                    width: 400,
                    height: 600,
                    wireframes: false,
                    background: '#1a202c'
                }
            });

            // Create runner
            var runner = Runner.create();

            // Add bodies
            var pegs = [];
            var rows = 10;
            var maxColumns = 15;
            var spacing = 30;
            var pegRadius = 3;

            for (var row = 0; row < rows; row++) {
                var columnsInRow = maxColumns - row;
                var startX = (row * spacing) / 2;

                for (var col = 0; col < columnsInRow; col++) {
                    var x = startX + (col * spacing) + spacing / 2;
                    var y = (row * spacing) + spacing;
                    var peg = Bodies.circle(x, y, pegRadius, {
                        isStatic: true,
                        render: {
                            fillStyle: '#4a5568'
                        }
                    });
                    pegs.push(peg);
                }
            }

            // Adjust canvas size and bucket positions
            var canvasWidth = maxColumns * spacing;
            var canvasHeight = (rows + 2) * spacing + 50;

            render.canvas.width = canvasWidth;
            render.canvas.height = canvasHeight;
            render.options.width = canvasWidth;
            render.options.height = canvasHeight;

            var buckets = [];
            var bucketWidth = spacing;
            var bucketHeight = 50;
            var multipliers = [0, 0.5, 1, 1.5, 2, 3, 5, 10, 3, 1.5, 0.5, 0, 0.5, 1, 1.5];

            var bucketTimers = [];
            var bucketIntervals = [];

            // Modify the bucket creation loop to initialize timers and intervals
            for (var i = 0; i < maxColumns; i++) {
                var x = i * spacing + spacing / 2;
                var y = canvasHeight - bucketHeight / 2;
                var bucket = Bodies.rectangle(x, y, bucketWidth, bucketHeight, {
                    isStatic: true,
                    isSensor: true,
                    render: {
                        fillStyle: '#4a5568'
                    },
                    bucketIndex: i
                });
                buckets.push(bucket);

                // Initialize timer and interval for this bucket
                bucketTimers.push(0);
                bucketIntervals.push(null);

                // Add multiplier text
                var multiplierText = Bodies.circle(x, y, 1, {
                    isStatic: true,
                    render: {
                        sprite: {
                            texture: createMultiplierTexture(multipliers[i]),
                            xScale: 0.5,
                            yScale: 0.5
                        }
                    }
                });
                buckets.push(multiplierText);
            }

            // Add all bodies to the world
            Composite.add(engine.world, [...pegs, ...buckets]);

            // Run the engine
            Runner.run(runner, engine);

            // Run the renderer
            Render.run(render);

            // Function to create multiplier texture
            function createMultiplierTexture(multiplier) {
                var canvas = document.createElement('canvas');
                canvas.width = 50;
                canvas.height = 20;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = 'white';
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(multiplier + 'x', canvas.width / 2, canvas.height / 2);
                return canvas.toDataURL();
            }

            // Function to spawn a ball
            function spawnBall(betAmount) {
                var ball = Bodies.circle(canvasWidth / 2, 10, 5, {
                    restitution: 0.5,
                    friction: 0.001,
                    density: 0.01,
                    render: {
                        fillStyle: '#f59e0b'
                    }
                });

                Composite.add(engine.world, ball);

                // Check for collision with buckets
                Matter.Events.on(engine, 'collisionStart', function(event) {
                    var pairs = event.pairs;
                    for (var i = 0; i < pairs.length; i++) {
                        var pair = pairs[i];
                        if ((pair.bodyA === ball && pair.bodyB.bucketIndex !== undefined) ||
                            (pair.bodyB === ball && pair.bodyA.bucketIndex !== undefined)) {
                            var bucket = pair.bodyA.bucketIndex !== undefined ? pair.bodyA : pair.bodyB;
                            var bucketIndex = bucket.bucketIndex;

                            // Increase the timer for this bucket
                            bucketTimers[bucketIndex] += 1000; // Add 1 second (1000ms)

                            // Clear any existing interval for this bucket
                            if (bucketIntervals[bucketIndex]) {
                                clearInterval(bucketIntervals[bucketIndex]);
                            }

                            // Set the bucket color to green
                            bucket.render.fillStyle = '#4ade80';

                            // Start a new interval to count down the timer
                            bucketIntervals[bucketIndex] = setInterval(() => {
                                bucketTimers[bucketIndex] -= 100; // Decrease by 100ms every 100ms
                                if (bucketTimers[bucketIndex] <= 0) {
                                    bucket.render.fillStyle = '#4a5568'; // Reset to original color
                                    clearInterval(bucketIntervals[bucketIndex]);
                                    bucketIntervals[bucketIndex] = null;
                                    bucketTimers[bucketIndex] = 0;
                                }
                            }, 100);

                            showResult(bucketIndex, betAmount);
                            setTimeout(() => {
                                Composite.remove(engine.world, ball);
                            }, 1000);
                            Matter.Events.off(engine, 'collisionStart');
                        }
                    }
                });
            }

            function showResult(bucket, betAmount) {
                const winMultiplier = multipliers[bucket];
                const winAmount = betAmount * winMultiplier;
                const profitLoss = winAmount - betAmount;

                document.getElementById('resultBucket').textContent = bucket + 1;
                document.getElementById('resultMultiplier').textContent = winMultiplier;
                document.getElementById('resultWinAmount').textContent = winAmount.toFixed(2);
                document.getElementById('resultProfitLoss').textContent = profitLoss.toFixed(2);
                document.getElementById('gameResult').style.display = 'block';

                // Update user balance via AJAX
                updateBalance(profitLoss);
            }

            function updateBalance(amount) {
                fetch('../api/update_balance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `amount=${amount}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('userBalance').textContent = parseFloat(data.newBalance).toFixed(2);
                        }
                    });
            }

            document.getElementById('plinkoForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const betAmount = parseFloat(document.getElementById('bet_amount').value);
                if (betAmount > 0) {
                    spawnBall(betAmount);
                }
            });
        </script>
    </body>

</html>