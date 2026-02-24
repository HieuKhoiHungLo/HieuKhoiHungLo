// Background Particles using Three.js
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('bg-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true });

    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Create circle texture for round particles
    const circleCanvas = document.createElement('canvas');
    circleCanvas.width = 64;
    circleCanvas.height = 64;
    const ctx = circleCanvas.getContext('2d');
    ctx.beginPath();
    ctx.arc(32, 32, 30, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    const circleTexture = new THREE.CanvasTexture(circleCanvas);

    // Particles
    const isMobile = window.innerWidth < 768;
    const particlesCount = isMobile ? 80 : 160;

    const posArray = new Float32Array(particlesCount * 3);
    const sizesArray = new Float32Array(particlesCount);

    for (let i = 0; i < particlesCount; i++) {
        posArray[i * 3] = (Math.random() - 0.5) * 18;
        posArray[i * 3 + 1] = (Math.random() - 0.5) * 18;
        posArray[i * 3 + 2] = (Math.random() - 0.5) * 18;

        // Varied sizes: mix of small, medium, large
        sizesArray[i] = 0.03 + Math.random() * 0.12;
    }

    const particlesGeometry = new THREE.BufferGeometry();
    particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
    particlesGeometry.setAttribute('aSize', new THREE.BufferAttribute(sizesArray, 1));

    // Custom shader for varied sizes
    const material = new THREE.ShaderMaterial({
        uniforms: {
            uColor: { value: new THREE.Color(0xBE1E2D) },
            uTexture: { value: circleTexture },
            uPixelRatio: { value: Math.min(window.devicePixelRatio, 2) }
        },
        vertexShader: `
            attribute float aSize;
            uniform float uPixelRatio;
            void main() {
                vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
                gl_PointSize = aSize * uPixelRatio * (200.0 / -mvPosition.z);
                gl_Position = projectionMatrix * mvPosition;
            }
        `,
        fragmentShader: `
            uniform vec3 uColor;
            uniform sampler2D uTexture;
            void main() {
                vec4 tex = texture2D(uTexture, gl_PointCoord);
                if (tex.a < 0.1) discard;
                gl_FragColor = vec4(uColor, tex.a * 0.35);
            }
        `,
        transparent: true,
        depthWrite: false
    });

    const particlesMesh = new THREE.Points(particlesGeometry, material);
    scene.add(particlesMesh);

    camera.position.z = 3;

    // Mouse Interaction
    let mouseX = 0;
    let mouseY = 0;

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

        // Slower rotation
        particlesMesh.rotation.y = elapsedTime * 0.025;
        particlesMesh.rotation.x = elapsedTime * 0.01;

        renderer.render(scene, camera);
    }

    // Visibility API to pause animation
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            isPageVisible = false;
            cancelAnimationFrame(animationId);
        } else {
            isPageVisible = true;
            clock.getDelta();
            animate();
        }
    });

    animate();

    // Resize
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        material.uniforms.uPixelRatio.value = Math.min(window.devicePixelRatio, 2);
    });
});
