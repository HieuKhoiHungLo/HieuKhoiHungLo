// Background Particles using Three.js
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('bg-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true });

    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);

    // Particles
    const particlesGeometry = new THREE.BufferGeometry();
    const isMobile = window.innerWidth < 768;
    const particlesCount = isMobile ? 40 : 80; // Performance optimization

    const posArray = new Float32Array(particlesCount * 3);

    for (let i = 0; i < particlesCount * 3; i++) {
        posArray[i] = (Math.random() - 0.5) * 15; // Spread
    }

    particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));

    const material = new THREE.PointsMaterial({
        size: 0.02,
        color: 0xBE1E2D, // HVU Red
        transparent: true,
        opacity: 0.4
    });

    const particlesMesh = new THREE.Points(particlesGeometry, material);
    scene.add(particlesMesh);

    camera.position.z = 3;

    // Mouse Interaction
    let mouseX = 0;
    let mouseY = 0;

    // Throttled mouse move
    let timeout;
    document.addEventListener('mousemove', (event) => {
        if (timeout) return;
        timeout = setTimeout(() => {
            mouseX = event.clientX;
            mouseY = event.clientY;
            timeout = null;
        }, 50);
    });

    const clock = new THREE.Clock();

    let animationId;
    let isPageVisible = true;

    function animate() {
        if (!isPageVisible) return;

        animationId = requestAnimationFrame(animate);

        const elapsedTime = clock.getElapsedTime();

        // Rotate entire system slowly
        particlesMesh.rotation.y = elapsedTime * 0.05;
        particlesMesh.rotation.x = elapsedTime * 0.02;

        renderer.render(scene, camera);
    }

    // Visibility API to pause animation
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            isPageVisible = false;
            cancelAnimationFrame(animationId);
        } else {
            isPageVisible = true;
            clock.getDelta(); // Prevent jump
            animate();
        }
    });

    animate();

    // Resize
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
