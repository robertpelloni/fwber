'use client'

import { useEffect, useState, useRef } from 'react'
import { MapContainer, TileLayer, Marker, Circle, useMapEvents } from 'react-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

// Fix for Leaflet default marker icon missing in Webpack/Next.js
// @ts-ignore
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
});

interface LocationPickerProps {
  value?: { lat: number; lng: number }
  onChange: (value: { lat: number; lng: number }) => void
  radius?: number // in meters
}

function MapEvents({ onChange }: { onChange: (latlng: { lat: number; lng: number }) => void }) {
  useMapEvents({
    click(e) {
      onChange(e.latlng)
    },
  })
  return null
}

export default function LocationPicker({ value, onChange, radius = 100 }: LocationPickerProps) {
  // Default to New York if no value provided
  const center = value || { lat: 40.7128, lng: -74.0060 }
  
  // Need to invalidate size after mount to ensure map renders correctly if hidden initially
  const [map, setMap] = useState<L.Map | null>(null)

  useEffect(() => {
    if (map) {
      setTimeout(() => {
        map.invalidateSize()
      }, 100)
    }
  }, [map])

  return (
    <div className="h-[300px] w-full rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 relative z-0">
      <MapContainer
        center={center}
        zoom={13}
        scrollWheelZoom={false}
        style={{ height: '100%', width: '100%' }}
        ref={setMap}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <MapEvents onChange={onChange} />
        {value && (
          <>
            <Marker position={value} />
            {radius && <Circle center={value} radius={radius} pathOptions={{ color: 'amber', fillColor: '#f59e0b', fillOpacity: 0.2 }} />}
          </>
        )}
      </MapContainer>
    </div>
  )
}
