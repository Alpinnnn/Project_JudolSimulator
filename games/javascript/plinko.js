// Matter.js module aliases
var Engine = Matter.Engine,
    Render = Matter.Render,
    Runner = Matter.Runner,
    Bodies = Matter.Bodies,
    Composite = Matter.Composite,
    Events = Matter.Events;

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
var buckets = [];
var rows = 10;
var maxColumns = 15;
var spacing = 30;
var pegRadius = 3;

// Create pegs
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

var bucketWidth = spacing;
var bucketHeight = 50;
var multipliers = [0, 0.5, 1, 1.5, 2, 3, 5, 10, 3, 1.5, 0.5, 0, 0.5, 1, 1.5];

// Create buckets
for (var i = 0; i < maxColumns; i++) {
    var x = i * spacing + spacing / 2;
    var y = canvasHeight - bucketHeight / 2;
    var bucket = Bodies.rectangle(x, y, bucketWidth, bucketHeight, {
        isStatic: true,
        render: {
            fillStyle: '#4a5568'
        },
        bucketIndex: i,
        label: 'bucket',
        isSensor: true  // Make bucket a sensor to allow balls to pass through
    });
    buckets.push(bucket);

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

// Run the engine and renderer
Runner.run(runner, engine);
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

// Array to store result history
let resultHistory = [];

// Set to keep track of balls that have already been processed
let processedBalls = new Set();

// Function to spawn a ball
function spawnBall(betAmount) {
    var ball = Bodies.circle(canvasWidth / 2, 10, 5, {
        restitution: 0.5,
        friction: 0.001,
        density: 0.01,
        render: {
            fillStyle: '#f59e0b'
        },
        label: 'ball',
        id: Math.random().toString(36).substr(2, 9), // Unique ID for each ball
        collisionFilter: {
            group: -1  // Balls won't collide with each other
        }
    });

    Composite.add(engine.world, ball);
}

// Function to animate bucket
function animateBucket(bucket) {
    const originalY = bucket.position.y;
    const animationDuration = 100; // ms
    const animationStart = Date.now();

    const animate = () => {
        const now = Date.now();
        const progress = (now - animationStart) / animationDuration;

        if (progress < 1) {
            const y = originalY + Math.sin(progress * Math.PI) * 5;
            Matter.Body.setPosition(bucket, { x: bucket.position.x, y: y });
            requestAnimationFrame(animate);
        } else {
            Matter.Body.setPosition(bucket, { x: bucket.position.x, y: originalY });
        }
    };

    animate();
}

// Event listener for collisions
Events.on(engine, 'collisionStart', function(event) {
    var pairs = event.pairs;
    for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i];
        if ((pair.bodyA.label === 'ball' && pair.bodyB.label === 'bucket') ||
            (pair.bodyB.label === 'ball' && pair.bodyA.label === 'bucket')) {
            var bucket = pair.bodyA.label === 'bucket' ? pair.bodyA : pair.bodyB;
            var ball = pair.bodyA.label === 'ball' ? pair.bodyA : pair.bodyB;
            
            // Check if this ball has already been processed
            if (!processedBalls.has(ball.id)) {
                processedBalls.add(ball.id);
                
                var bucketIndex = bucket.bucketIndex;

                // Animate bucket
                animateBucket(bucket);

                // Remove the ball after a short delay
                setTimeout(() => {
                    Composite.remove(engine.world, ball);
                    processedBalls.delete(ball.id);
                }, 100);

                // Show result and update balance
                showResult(bucketIndex, parseFloat(document.getElementById('bet_amount').value));
            }
        }
    }
});

// Function to show result
function showResult(bucket, betAmount) {
    const winMultiplier = multipliers[bucket];
    const winAmount = betAmount * winMultiplier;
    const profitLoss = winAmount - betAmount;

    // Update user balance via AJAX
    updateBalance(profitLoss);

    // Add result to history
    resultHistory.unshift({
        amount: profitLoss,
        timestamp: Date.now()
    });

    // Keep only the last 5 results
    if (resultHistory.length > 5) {
        resultHistory.pop();
    }

    // Update the result history display
    updateResultHistoryDisplay();
}

// Function to update result history display
function updateResultHistoryDisplay() {
    const historyContainer = document.getElementById('resultHistory');
    historyContainer.innerHTML = '';

    resultHistory.forEach((result, index) => {
        const resultElement = document.createElement('div');
        resultElement.classList.add('result-item', 'mb-2', 'p-2', 'rounded');
        resultElement.style.opacity = 1 - (index * 0.2); // Fade out older results

        const amount = result.amount.toFixed(2);
        const sign = result.amount >= 0 ? '+' : '-';
        const color = result.amount >= 0 ? 'text-green-500' : 'text-red-500';

        resultElement.innerHTML = `<span class="${color}">${sign}$${Math.abs(amount)}</span>`;
        historyContainer.appendChild(resultElement);
    });

    // Remove oldest result after 5 seconds
    setTimeout(() => {
        if (resultHistory.length > 0) {
            resultHistory.pop();
            updateResultHistoryDisplay();
        }
    }, 5000);
}

// Function to update balance
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

// Event listener for the form submission
document.getElementById('plinkoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const betAmount = parseFloat(document.getElementById('bet_amount').value);
    if (betAmount > 0) {
        spawnBall(betAmount);
    }
});

// Initialize result history display
updateResultHistoryDisplay();