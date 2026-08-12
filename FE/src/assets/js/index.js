(function() {
  "use strict";

jQuery('#geographic-map-2').vectorMap(
{
  map: 'world_mill_en',
  backgroundColor: 'transparent',
  borderColor: '#818181',
  borderOpacity: 0.25,
  borderWidth: 1,
  zoomOnScroll: false,
  color: '#009efb',
  regionStyle : {
      initial : {
        fill : '#008cff'
      }
    },
  markerStyle: {
    initial: {
      r: 9,
      'fill': '#fff',
      'fill-opacity':1,
      'stroke': '#000',
      'stroke-width' : 5,
      'stroke-opacity': 0.4
              },
              },
  enableZoom: true,
  hoverColor: '#009efb',
  markers : [{
      latLng : [21.00, 78.00],
      name : 'Lorem Ipsum Dollar'
    
    }],
  hoverOpacity: null,
  normalizeFunction: 'linear',
  scaleColors: ['#b6d6ff', '#005ace'],
  selectedColor: '#c9dfaf',
  selectedRegions: [],
  showTooltip: true,
});

 });	 
 