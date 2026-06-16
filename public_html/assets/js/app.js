const currentPath = window.location.pathname.replace(/\/$/, '') || '/';

document.querySelectorAll('.nav a').forEach((link) => {
  const linkPath = new URL(link.href).pathname.replace(/\/$/, '') || '/';

  if (linkPath === currentPath) {
    link.setAttribute('aria-current', 'page');
  }
});

const growTextarea = (textarea) => {
  textarea.style.height = 'auto';
  textarea.style.height = `${textarea.scrollHeight}px`;
};

document.querySelectorAll('textarea[data-autogrow]').forEach((textarea) => {
  growTextarea(textarea);
  textarea.addEventListener('input', () => growTextarea(textarea));
});

const loadYandexMaps = (apiKey) => new Promise((resolve, reject) => {
  if (window.ymaps) {
    window.ymaps.ready(() => resolve(window.ymaps));
    return;
  }

  const script = document.createElement('script');
  script.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(apiKey)}&lang=ru_RU`;
  script.async = true;
  script.onload = () => window.ymaps.ready(() => resolve(window.ymaps));
  script.onerror = () => reject(new Error('Yandex Maps script failed to load'));
  document.head.append(script);
});

const formatCoordinate = (value) => Number(value).toFixed(7);

const reverseGeocode = async (latitude, longitude) => {
  const response = await fetch('/api/geocode/reverse', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ latitude, longitude }),
  });

  if (!response.ok) {
    return { ok: false, message: 'Адрес не удалось определить автоматически' };
  }

  return response.json();
};

const initSubmitMap = async (mapElement) => {
  const apiKey = mapElement.dataset.apiKey;

  if (!apiKey) {
    return;
  }

  const form = mapElement.closest('form');
  const latitudeInput = form.querySelector('[data-latitude-input]');
  const longitudeInput = form.querySelector('[data-longitude-input]');
  const cityInput = form.querySelector('[data-city-input]');
  const addressInput = form.querySelector('[data-address-input]');
  const coordinateOutput = form.querySelector('[data-coordinate-output]');
  const rawLatitude = mapElement.dataset.latitude || latitudeInput.value;
  const rawLongitude = mapElement.dataset.longitude || longitudeInput.value;
  const startLatitude = rawLatitude === '' ? Number.NaN : Number(rawLatitude);
  const startLongitude = rawLongitude === '' ? Number.NaN : Number(rawLongitude);
  const hasStartPoint = Number.isFinite(startLatitude) && Number.isFinite(startLongitude);
  const center = hasStartPoint ? [startLatitude, startLongitude] : [55.751244, 37.618423];
  const ymaps = await loadYandexMaps(apiKey);
  const map = new ymaps.Map(mapElement.id, {
    center,
    zoom: hasStartPoint ? 14 : 9,
    controls: ['zoomControl', 'geolocationControl'],
  });
  let placemark = null;

  const setStatus = (text) => {
    if (coordinateOutput) {
      coordinateOutput.textContent = text;
    }
  };

  const setPoint = async (coords, shouldCenter = false) => {
    const latitude = Number(coords[0]);
    const longitude = Number(coords[1]);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
      return;
    }

    const formattedLatitude = formatCoordinate(latitude);
    const formattedLongitude = formatCoordinate(longitude);
    latitudeInput.value = formattedLatitude;
    longitudeInput.value = formattedLongitude;
    setStatus(`Точка выбрана: ${formattedLatitude}, ${formattedLongitude}`);

    if (!placemark) {
      placemark = new ymaps.Placemark([latitude, longitude], {}, {
        draggable: true,
        preset: 'islands#greenDotIcon',
      });
      placemark.events.add('dragend', () => {
        setPoint(placemark.geometry.getCoordinates());
      });
      map.geoObjects.add(placemark);
    } else {
      placemark.geometry.setCoordinates([latitude, longitude]);
    }

    if (shouldCenter) {
      map.setCenter([latitude, longitude], 14);
    }

    try {
      const result = await reverseGeocode(formattedLatitude, formattedLongitude);

      if (result.ok) {
        if (result.city && cityInput) {
          cityInput.value = result.city;
        }

        if (result.address && addressInput) {
          addressInput.value = result.address;
        }
      } else if (result.message) {
        setStatus(result.message);
      }
    } catch {
      setStatus('Адрес не удалось определить автоматически');
    }
  };

  if (hasStartPoint) {
    setPoint(center);
  }

  map.events.add('click', (event) => {
    setPoint(event.get('coords'), true);
  });
};

document.querySelectorAll('[data-yandex-map]').forEach((mapElement) => {
  initSubmitMap(mapElement).catch(() => {
    mapElement.innerHTML = '<div class="map-loading">Карту не удалось загрузить. Адрес можно заполнить вручную.</div>';
  });
});
