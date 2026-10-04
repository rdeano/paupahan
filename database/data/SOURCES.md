# Davao City reference address data - REPORT

Generated 2026-10-04 for Logicc (paupahan rental listings). Every row comes from a downloaded source listed below; nothing was filled from memory.

## Files

| File | Rows (excl. header) |
|---|---|
| barangays.csv (psgc_code,name,district) | 182 |
| streets.csv (name,barangay_psgc_code,osm_highway,source) | 3449 |
| streets_unassigned.csv (name,osm_highway) | 2066 |
| barangay_district_sources.csv (per-barangay audit trail of district sources) | 182 |

## Sources

1. **PSA PSGC, 2nd Quarter 2026 Publication Datafile** (publication date in file metadata: 30 June 2026). This was the latest file linked on the PSA PSGC page as captured 2026-09-26.
   - Official URL: https://psa.gov.ph/system/files/scd/PSGC-2Q-2026-Publication-Datafile.xlsx
   - **Direct download from psa.gov.ph was blocked** (Cloudflare "Just a moment..." challenge, HTTP 403, on both the page and the file). The file was taken from the Internet Archive's unmodified capture of that exact PSA URL: http://web.archive.org/web/20260904120257id_/https://psa.gov.ph/system/files/scd/PSGC-2Q-2026-Publication-Datafile.xlsx (captured 2026-09-04). Checked that it was the latest file using the page capture http://web.archive.org/web/20260926174542/https://psa.gov.ph/classification/psgc.
   - SHA-256: 31892bc2bdde3ea0682562d9412b5bab4d45a0be5e5a5b4f6c9d7714b94bca5d (3,250,889 bytes). Sheet `PSGC`, rows whose 10-digit code starts with 1130700 (City of Davao, 1130700000, HUC; old 9-digit correspondence code 112402000).
   - It is worth checking psa.gov.ph in a browser to confirm that no 3Q 2026 release has come out since 2026-09-26.
2. **OpenStreetMap via Overpass API** (https://overpass-api.de/api/interpreter), queried 2026-10-04. OSM data timestamp 2026-10-04T07:56:49Z, areas timestamp 2026-10-03T14:05:21Z. The query texts are saved as q_areas.txt, q_nodes.txt, q_streets.txt and q_allstreets.txt.
3. **Wikipedia, "Districts of Davao City"** (https://en.wikipedia.org/wiki/Districts_of_Davao_City, revision 1368756755). Used only to cross-check districts. The article's own references are old: NSCB ActiveStats and Davao City directory pages, archived 2006-2007.

## Barangays

- PSGC barangays in the City of Davao: **182** (all at geographic level `Bgy`).
- `psgc_code` is the 10-digit PSGC. `name` is the PSGC `Name` column with trailing whitespace trimmed. The letter Ñ is kept (Catalunan Pequeño, Santo Niño).
- **Poblacion naming:** the 2Q 2026 datafile does NOT put "(Pob.)" in the name. The name is, for example, `Barangay 1-A`, and `Pob.` is in a separate `Status` column. 48 barangays have Status = Pob.: the 40 downtown barangays (Barangay 1-A to Barangay 40-D) plus Baguio, Buhangin, Bunawan, Calinan, Paquibato, Talomo, Toril and Tugbok. I kept the Name column as published. The status is recorded in barangay_district_sources.csv (`psgc_status`) if you want to display "(Pob.)".
- PSGC old names (from the `Old names` column): Fatima (Benowang), Alejandra Navarro (Lasang), San Isidro (Licanan), Suawan (Tuli), Centro (San Juan). These are in the sidecar file.

### District

The PSGC has no administrative-district level. District values come from OpenStreetMap tags and Wikipedia. **All available sources agree for every barangay (0 conflicts):**
- 116 barangays: three sources agree. These are the OSM barangay boundary relation `is_in:district`, the OSM barangay place node `is_in:district` (matched by `ref` = PSGC code), and Wikipedia.
- 65 barangays (no OSM boundary): two sources agree, the OSM place node (`ref` = PSGC code, `is_in:district`) and Wikipedia.
- 1 barangay (Malabog): two sources agree, the OSM relation and Wikipedia. It has no admin_level=10 place node with its ref.
- **Barangays with no district: none (0).**
- Caveat: the OSM `is_in:district` tags and Wikipedia are both community-edited, and they may not be independent of each other. Neither is an official Davao City government source, and I could not find a downloadable official city list. Counts by district: Poblacion 40, Toril 25, Calinan 19, Tugbok 18, Talomo 14, Buhangin 13, Paquibato 13, Marilog 12, Agdao 11, Bunawan 9, Baguio 8 (total 182). These match the per-district counts in the Wikipedia table.
- Wikipedia names matched to PSGC names by fuzzy spelling (please review): 'Leon Garcia' -> 'Leon Garcia, Sr.'; 'Tungkalan' -> 'Tungakalan'; 'Balenggaeng' -> 'Balengaeng'; 'Biao Guinga' -> 'Biao Guianga'. Wikipedia's "X Proper" entries were matched to the PSGC barangay X.

## OSM barangay boundaries

- admin_level=10 relations inside Davao City: **117**, against **182** barangays in the PSGC. All 117 matched to the PSGC: 116 by their `ref` tag (= 10-digit PSGC) and 1 by name.
- **Unmatched OSM barangay names: none.**
- Anomalies:
  - Malabog (relation 19106968) has `ref=1102402065`, which is not a Davao City PSGC code. It was matched by name to 1130700065 Malabog.
  - Some OSM names differ from the PSGC even though the ref matches: 'Kapitan Tomas Monteverde Sr.' (PSGC 'Kap. Tomas Monteverde, Sr.'), 'Paciano Bangoy' (PSGC 'Gov. Paciano Bangoy'), 'Vicente Duterte' (PSGC 'Gov. Vicente Duterte'), and **'Lampiano' (PSGC 'Lampianao', a spelling difference)**.
  - Many OSM barangay and district relations carry `note=boundary approximate` or `boundary alignment approximate`. Street-to-barangay assignment is only as accurate as these boundaries.
- **PSGC barangays with no OSM boundary (65).** Their streets cannot be assigned spatially and are in streets_unassigned.csv: Acacia (1130700001), Alambre (1130700003), Atan-Awe (1130700004), Bago Oshiro (1130700006), Balengaeng (1130700009), Bangkas Heights (1130700012), Baracatan (1130700013), Bato (1130700014), Bayabas (1130700015), Biao Escuela (1130700016), Biao Guianga (1130700017), Binugao (1130700019), Buhangin (1130700021), Bunawan (1130700022), Cabantian (1130700023), Callawa (1130700027), Camansi (1130700028), Catigan (1130700032), Communal (1130700035), Crossing Bayabas (1130700036), Daliao (1130700040), Daliaon Plantation (1130700041), Eden (1130700044), Gatungan (1130700047), Ilang (1130700049), Indangan (1130700050), Kilate (1130700051), Alejandra Navarro (1130700056), Lizada (1130700057), Los Amigos (1130700058), Lubogan (1130700059), Mahayag (1130700064), Manambulan (1130700068), Mandug (1130700069), Manuel Guianga (1130700070), Marapangi (1130700072), Matina Biao (1130700078), Mintal (1130700079), Mudiang (1130700080), Mulig (1130700081), New Carmen (1130700082), New Valencia (1130700083), Pampanga (1130700086), Panacan (1130700087), San Isidro (1130700100), Sasa (1130700101), Sibulan (1130700102), Sirawan (1130700104), Tacunan (1130700110), Tagakpan (1130700112), Tagluno (1130700113), Tagurano (1130700114), Talandang (1130700115), Tibuloy (1130700123), Tibungco (1130700124), Tigatto (1130700125), Toril (1130700126), Tugbok (1130700127), Tungakalan (1130700128), Ula (1130700129), Angalan (1130700174), Santo Niño (1130700193), Waan (1130700195), Alfonso Angliongto Sr. (1130700197), Vicente Hizon Sr. (1130700198).

## Streets

- Included: ways with `highway` in trunk, primary, secondary, tertiary, unclassified, residential, living_street or service, AND a `name` tag. Every included way has a name, so service roads appear only when named. `_link` roads, footways and paths are excluded.
- How streets were assigned to barangays: Overpass, for each admin_level=10 relation, `map_to_area` and then `way(area.a)`. A way counts as being in a barangay if it is inside or crosses that barangay's area. A street that crosses several barangays gets one row per barangay. Ways that run along a barangay border may be counted in both barangays.
- streets.csv: 3449 rows, unique on (name, barangay_psgc_code). It has 2333 distinct names and covers all 117 barangays that have an OSM boundary. Names are trimmed and otherwise unchanged (for example, 'Lake  Tikub Street' keeps the double space it has in OSM). In 104 cases one name in one barangay has segments of different highway classes; `osm_highway` then holds the most significant class.
- streets_unassigned.csv: 2066 distinct names, from the 4,982 named ways in the city area that fall in none of the 117 OSM barangay areas. Most are in the 65 barangays that have no boundary. 555 of these names also appear in streets.csv, because a road with the same name (or another segment of the same road) lies in a mapped barangay. The city-wide query can also pick up ways that cross the Davao City boundary into neighbouring municipalities.
- OSM street names are entered by volunteers. Coverage is far from complete, especially in the rural districts (Marilog, Paquibato, Baguio, Calinan).

## Licensing / attribution

- OpenStreetMap data (streets, boundaries, district tags) is (c) OpenStreetMap contributors and licensed under the **Open Database License (ODbL) 1.0**. The app must show attribution such as "(c) OpenStreetMap contributors" with a link to https://www.openstreetmap.org/copyright. A derivative database that is used publicly must be offered under ODbL (share-alike).
- Wikipedia text is CC BY-SA 4.0. Only facts (the district each barangay belongs to) were used, to cross-check.
- The PSGC is a public PSA publication. Cite it as "Philippine Statistics Authority, PSGC 2Q 2026".

## Raw inputs kept in this folder

PSGC-2Q-2026-Publication-Datafile.xlsx, psgc_davao_raw.json, osm_admin_tags.json, osm_ref_nodes.json, osm_streets_by_brgy.json, osm_streets_all.json, osm_brgy_match.json, wp_districts.wikitext, district_sources.json, and the build_*.py / write_*.py scripts. The `pylib/` folder holds openpyxl, installed locally to read the xlsx.
