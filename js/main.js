var sidebar = new ol.control.Sidebar({ element: 'sidebar', position: 'right' });
var jsonFiles, filesLength, fileKey = 0;

var projection = ol.proj.get('EPSG:3857');
var projectionExtent = projection.getExtent();
var size = ol.extent.getWidth(projectionExtent) / 256;
var resolutions = new Array(20);
var matrixIds = new Array(20);
for (var z = 0; z < 20; ++z) {
  // generate resolutions and matrixIds arrays for this WMTS
  resolutions[z] = size / Math.pow(2, z);
  matrixIds[z] = z;
}

var sidebarTitle = document.getElementById('sidebarTitle');
var content = document.getElementById('sidebarContent');
var lineHead = {};
var currentFeature = false;
var lastCunli = false;
var lastFeature = false;
var lastFeatureType = '';


var appView = new ol.View({
  center: ol.proj.fromLonLat([120.221507, 23.000694]),
  zoom: 14
});

var vectorPoints = new ol.layer.Vector({
  source: new ol.source.Vector({
    format: new ol.format.GeoJSON({
      featureProjection: appView.getProjection()
    })
  }),
  style: pointStyle
});

var a2Points = new ol.layer.Vector({
  source: new ol.source.Vector({
    format: new ol.format.GeoJSON({
      featureProjection: appView.getProjection()
    })
  }),
  style: a2Style
});

var cunli = new ol.layer.Vector({
  source: new ol.source.Vector({
    url: 'https://kiang.github.io/taiwan_basecode/cunli/topo/20210324.json',
    format: new ol.format.TopoJSON({
      featureProjection: appView.getProjection()
    })
  }),
  style: cunliStyle
});

var baseLayer = new ol.layer.Tile({
  source: new ol.source.WMTS({
    matrixSet: 'EPSG:3857',
    format: 'image/png',
    url: 'https://wmts.nlsc.gov.tw/wmts',
    layer: 'EMAP',
    tileGrid: new ol.tilegrid.WMTS({
      origin: ol.extent.getTopLeft(projectionExtent),
      resolutions: resolutions,
      matrixIds: matrixIds
    }),
    style: 'default',
    wrapX: true,
    attributions: '<a href="http://maps.nlsc.gov.tw/" target="_blank">國土測繪圖資服務雲</a>'
  }),
  opacity: 1
});

var map = new ol.Map({
  layers: [baseLayer, cunli, vectorPoints, a2Points],
  target: 'map',
  view: appView
});

map.addControl(sidebar);
var pointClicked = false;
map.on('singleclick', function (evt) {
  pointClicked = false;
  map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
    if (false === pointClicked) {
      firstPosDone = true;
      var p = feature.getProperties();
      currentFeature = feature;
      if (lastFeature) {
        if (lastFeatureType === 'point') {
          if (p['事故類別名稱'] === 'A1') {
            lastFeature.setStyle(pointStyle);
          } else {
            currentFeature.setStyle(a2Style);
          }
        } else {
          lastFeature.setStyle(cunliStyle);
        }
      }
      var message = '';

      if (p['事故類別名稱']) {
        var lonLat = ol.proj.toLonLat(p.geometry.getCoordinates());
        message += '<table class="table table-dark">';
        message += '<tbody>';
        for (k in p) {
          if (k !== 'geometry') {
            message += '<tr><th scope="row">' + k + '</th><td>' + p[k] + '</td></tr>';
          }
        }

        message += '<tr><td colspan="2">';
        message += '<hr /><div class="btn-group-vertical" role="group" style="width: 100%;">';
        message += '<a href="https://www.google.com/maps/dir/?api=1&destination=' + lonLat[1] + ',' + lonLat[0] + '&travelmode=driving" target="_blank" class="btn btn-info btn-lg btn-block">Google 導航</a>';
        message += '<a href="https://wego.here.com/directions/drive/mylocation/' + lonLat[1] + ',' + lonLat[0] + '" target="_blank" class="btn btn-info btn-lg btn-block">Here WeGo 導航</a>';
        message += '<a href="https://bing.com/maps/default.aspx?rtp=~pos.' + lonLat[1] + '_' + lonLat[0] + '" target="_blank" class="btn btn-info btn-lg btn-block">Bing 導航</a>';
        message += '</div></td></tr>';
        message += '</tbody></table>';
        sidebarTitle.innerHTML = p['發生地點'];
        if (p['事故類別名稱'] === 'A1') {
          currentFeature.setStyle(pointStyle);
        } else {
          currentFeature.setStyle(a2Style);
        }

        lastFeatureType = 'point';
      } else if (p.VILLCODE) {
        lastCunli = currentFeature;
        if (cunliMeta[p.VILLCODE]) {
          var cityCode = p.VILLCODE.substring(0, 5);
          var a2Features = [];
          $.get('data/cunli_a2/' + cityCode + '/' + p.VILLCODE + '.csv', {}, function (c) {
            var lines = $.csv.toArrays(c);
            for (k in lines) {
              var longitude = parseFloat(lines[k][49]);
              if (!Number.isNaN(longitude)) {
                var pointFeature = new ol.Feature({
                  geometry: new ol.geom.Point(
                    ol.proj.fromLonLat([longitude, parseFloat(lines[k][50])])
                  )
                });
                var a2Properties = {};
                for (lk in lineHead) {
                  a2Properties[lineHead[lk]] = lines[k][lk];
                }
                pointFeature.setProperties(a2Properties);
                a2Features.push(pointFeature);
              }
            }
          }).then(function () {
            var vSource = a2Points.getSource();
            vSource.clear();
            vSource.addFeatures(a2Features);
          });
          message += '<table class="table table-dark">';
          message += '<tbody>';
          message += '<tr><th scope="row">A1 數量</th><td>' + cunliMeta[p.VILLCODE].a1 + '</td></tr>';
          message += '<tr><th scope="row">A2 數量</th><td>' + cunliMeta[p.VILLCODE].a2 + '</td></tr>';
          message += '<tr><th scope="row">總數</th><td>' + cunliMeta[p.VILLCODE].total + '</td></tr>';
          message += '</tbody></table>';
        } else {
          message = '';
        }
        sidebarTitle.innerHTML = p.COUNTYNAME + p.TOWNNAME + p.VILLNAME;
        currentFeature.setStyle(cunliStyle);
        lastFeatureType = 'cunli';
      }
      lastFeature = currentFeature;
      content.innerHTML = message;
      sidebar.open('home');
      pointClicked = true;
    }
  });
});

function pointStyle(f) {
  var p = f.getProperties(), color = '#ff0000', stroke, radius = 15, pointCount = 5;
  if (f === currentFeature) {
    stroke = new ol.style.Stroke({
      color: '#000',
      width: 5
    });
    radius = 25;
  } else {
    stroke = new ol.style.Stroke({
      color: '#fff',
      width: 2
    });
  }

  return new ol.style.Style({
    image: new ol.style.RegularShape({
      radius: radius,
      points: pointCount,
      fill: new ol.style.Fill({
        color: color
      }),
      stroke: stroke
    })
  })
}

function a2Style(f) {
  var p = f.getProperties(), radius, strokeWidth = 1;
  if (f === currentFeature) {
    radius = 15;
    strokeWidth = 3;
  } else {
    radius = 8;
  }

  return new ol.style.Style({
    image: new ol.style.RegularShape({
      radius: radius,
      points: 3,
      fill: new ol.style.Fill({
        color: '#cccc00'
      }),
      stroke: new ol.style.Stroke({
        color: '#f00',
        width: strokeWidth
      })
    })
  })
}

function cunliStyle(f) {
  var p = f.getProperties();
  var color = 'rgba(149,78,44,0.7)';
  var strokeWidth = 1;
  if (f === lastCunli) {
    strokeWidth = 5;
    color = 'rgba(255,255,255,0)';
  } else if (cunliMeta[p.VILLCODE]) {
    if (cunliMeta[p.VILLCODE].total > 50) {
      color = 'rgba(30,16,9,0.7)';
    } else if (cunliMeta[p.VILLCODE].total > 30) {
      color = 'rgba(75,39,22,0.7)';
    } else if (cunliMeta[p.VILLCODE].total > 10) {
      color = 'rgba(104,55,31,0.7)';
    } else if (cunliMeta[p.VILLCODE].total > 5) {
      color = 'rgba(134,70,40,0.7)';
    } else if (cunliMeta[p.VILLCODE].total > 0) {
      color = 'rgba(164,85,40,0.7)';
    }
  } else {
    color = 'rgba(255,255,255,0)';
  }
  return new ol.style.Style({
    stroke: new ol.style.Stroke({
      color: 'rgba(0,0,0,0.8)',
      width: strokeWidth
    }),
    fill: new ol.style.Fill({
      color: color
    })
  })
}

var geolocation = new ol.Geolocation({
  projection: appView.getProjection()
});

geolocation.setTracking(true);

geolocation.on('error', function (error) {
  console.log(error.message);
});

var positionFeature = new ol.Feature();

positionFeature.setStyle(new ol.style.Style({
  image: new ol.style.Circle({
    radius: 6,
    fill: new ol.style.Fill({
      color: '#3399CC'
    }),
    stroke: new ol.style.Stroke({
      color: '#fff',
      width: 2
    })
  })
}));

var firstPosDone = false;
geolocation.on('change:position', function () {
  var coordinates = geolocation.getPosition();
  positionFeature.setGeometry(coordinates ? new ol.geom.Point(coordinates) : null);
  if (false === firstPosDone) {
    appView.setCenter(coordinates);
    firstPosDone = true;
  }
});

new ol.layer.Vector({
  map: map,
  source: new ol.source.Vector({
    features: [positionFeature]
  })
});

$('#btn-geolocation').click(function () {
  var coordinates = geolocation.getPosition();
  if (coordinates) {
    appView.setCenter(coordinates);
  } else {
    alert('目前使用的設備無法提供地理資訊');
  }
  return false;
});

var cunliMeta = {};

$.getJSON('data/cunli.json', {}, function (r) {
  cunliMeta = r;
});
var pointFeatures = [];
vectorPoints.setZIndex(100);
var currentYear = new Date().getFullYear();
$.get('data/' + currentYear + '/a1.csv', {}, function (c) {
  var lines = $.csv.toArrays(c);
  for (k in lines) {
    if (k > 0) {
      if (lines[k][33] != 1) {
        continue;
      }
      var longitude = parseFloat(lines[k][49]);
      if (!Number.isNaN(longitude)) {
        var pointFeature = new ol.Feature({
          geometry: new ol.geom.Point(
            ol.proj.fromLonLat([longitude, parseFloat(lines[k][50])])
          )
        });
        var p = {};
        p.type = 'a1';
        for (lk in lineHead) {
          p[lineHead[lk]] = lines[k][lk];
        }
        pointFeature.setProperties(p);
        pointFeatures.push(pointFeature);
      }
    } else {
      lineHead = lines[k];
    }
  }
}).then(function () {
  var vSource = vectorPoints.getSource();
  vSource.addFeatures(pointFeatures);

  cunli.getSource().refresh();
});