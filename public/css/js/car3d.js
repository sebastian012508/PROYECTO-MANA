import * as THREE from 'https://esm.sh/three@0.160.0';
import { GLTFLoader } from 'https://esm.sh/three@0.160.0/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'https://esm.sh/three@0.160.0/examples/jsm/controls/OrbitControls.js';

const container = document.getElementById('contenedor-auto-3d');

if (container) {
  const scene = new THREE.Scene();

  const camera = new THREE.PerspectiveCamera(
    40,
    container.clientWidth / container.clientHeight,
    0.1,
    100
  );
  camera.position.set(3.8, 1.8, 4.2);

  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.2;
  container.appendChild(renderer.domElement);

  // Iluminación
  scene.add(new THREE.AmbientLight(0xffffff, 1.4));

  const keyLight = new THREE.DirectionalLight(0xffffff, 2.5);
  keyLight.position.set(5, 8, 5);
  scene.add(keyLight);

  const rimLight = new THREE.DirectionalLight(0x2dd4bf, 1.8);
  rimLight.position.set(-5, 3, -4);
  scene.add(rimLight);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.enableZoom = false;
  controls.enablePan = false;
  controls.maxPolarAngle = Math.PI / 2.05;
  controls.minPolarAngle = Math.PI / 4;

  const loader = new GLTFLoader();
  let carModel = null;

  // Prueba automáticamente las rutas más comunes en arquitectura MVC
  const posiblesRutas = [
    'public/models/wrecked_car.glb',
    './public/models/wrecked_car.glb',
    'models/wrecked_car.glb',
    '/public/models/wrecked_car.glb'
  ];

  function intentarCargar(indice) {
    if (indice >= posiblesRutas.length) {
      console.error('❌ No se encontró wrecked_car.glb en ninguna ruta. Verifica que el archivo esté dentro de public/models/ y se llame exactamente wrecked_car.glb');
      return;
    }

    loader.load(
      posiblesRutas[indice],
      (gltf) => {
        console.log('✅ Modelo cargado desde:', posiblesRutas[indice]);
        carModel = gltf.scene;

        const box = new THREE.Box3().setFromObject(carModel);
        const center = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 3.8 / maxDim;

        carModel.scale.setScalar(scale);
        carModel.position.sub(center.multiplyScalar(scale));
        carModel.rotation.y = -0.4;
        scene.add(carModel);
      },
      undefined,
      () => {
        // Si falla esta ruta, intenta con la siguiente
        intentarCargar(indice + 1);
      }
    );
  }

  intentarCargar(0);

  function animate() {
    requestAnimationFrame(animate);
    if (carModel) {
      carModel.rotation.y += 0.004;
    }
    controls.update();
    renderer.render(scene, camera);
  }
  animate();

  window.addEventListener('resize', () => {
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
  });
} else {
  console.error('❌ No se encontró el div #contenedor-auto-3d en el HTML');
}