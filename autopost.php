<?php
// 실행 시간 측정 시작
$startTime = microtime(true);

// 1. wp-load.php 경로 설정
require_once('/var/www/tripjoa_sweden/wp-load.php'); 

// 2. 데이터베이스 접속 정보 설정
$dbHost = "15.165.178.34"; // 서버 주소
$dbUsername = "root"; // 사용자 이름
$dbPassword = "a20110602"; // 비밀번호
$dbName = "agoda"; // 데이터베이스 이름

// 3. 어필리에이트 ID를 적어주세요
$affiliateId = "A100688913";

// 4. 이미지 파일 시스템과 웹 경로 생성
$basePath = "/var/www/tripjoa_sweden/hotelsImages"; // 파일질라에 이미지 경로
$webPath = "https://sweden.tripjoa.net/hotelsImages"; // 웹사이트 주소에 이미지 디렉터리 추가

// MySQLi 객체를 사용하여 데이터베이스 접속
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
$conn->set_charset("utf8mb4");


// 접속 오류 확인
if ($conn->connect_error) {
    die("접속 실패: " . $conn->connect_error);
}

// 5. 포스팅할 조건을 입력하는 곳입니다 (지금은 posted 가 0으로 한번도 업로드 되지않은 호텔중에, 사진캡쳐가 완료 된 호텔중에서 랜덤으로 1개 선정하여 포스팅하게되어있습니다)
$query = "SELECT * FROM sweden WHERE posted = 0 AND screen = 1 AND accessibility IS NOT NULL AND accessibility <> '' ORDER BY RAND() LIMIT 1;";
$result = $conn->query($query);



if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
//이미지
function sanitizeFolderName($name, $isCityName = false) {
    $processedName = $name;
    if ($isCityName) {
        $processedName = str_replace(' ', '', $processedName);
        $processedName = str_replace('/', '-', $processedName);
    } else {
        $processedName = str_replace(' ', '-', $processedName);
        $processedName = str_replace('/', '-', $processedName);
    }
    return preg_replace('/-+/', '-', $processedName);
}

// 호텔의 국가명, 도시명, 호텔 이름을 변수로 저장
$countryName = $row['country']; 
$cityName = $row['city']; 
$hotelName = $row['hotel_translated_name'];

// sanitizeFolderName 함수를 사용하여 경로에 사용할 이름 처리
$countryFolderName = sanitizeFolderName($countryName);
$cityFolderName = sanitizeFolderName($cityName, true);
$hotelFolderName = sanitizeFolderName($hotelName);



// 실제 경로 생성
$realPath = "{$basePath}/{$countryFolderName}/{$cityFolderName}/{$hotelFolderName}";
$webUrl = "{$webPath}/{$countryFolderName}/{$cityFolderName}/{$hotelFolderName}";

// 이미지 파일 목록을 확장
$imageKeywords = ['header', 'luxury', 'view', 'experience', 'resort', 'amenity', 'leisure', 'suite', 'serenity', 'retreat', 'comfort', 'checkIn_info', 'point', 'hotelName', 'service'];
$images = [];
$additionalImages = []; // 추가 이미지 저장을 위한 배열

foreach ($imageKeywords as $keyword) {
    // 파일명 패턴을 생성할 때 '-'와 '_' 모두 고려
    $filePathPatterns = [
        "{$realPath}/{$keyword}-*.webp", 
        "{$realPath}/{$keyword}_*.webp" 
    ];
    
    foreach ($filePathPatterns as $filePathPattern) {
        foreach (glob($filePathPattern) as $filename) {
            // 웹 경로로 변환
            $webImage = str_replace($basePath, $webPath, $filename);
            // 특정 키워드에 대한 이미지 태그를 별도의 변수에 저장
            if (in_array($keyword, ['checkIn_info', 'point', 'hotelName', 'service'])) {
                $additionalImages[$keyword][] = "<div class='images' style='display: block; margin: 0 auto; text-align: center; max-width: 100%;'><img src='{$webImage}' alt='{$keyword}' style='max-width: 100%; height: auto;'></div>";
            } else {
                // 일반 이미지 태그 생성, div에 중앙 정렬을 위한 스타일 추가
                $images[] = "<div class='images' style='display: block; margin: 0 auto; max-width: 800px;'><img src='{$webImage}' alt='{$keyword}' style='max-width: 100%; height: auto;'></div>";
            }
        }
    }
}

// 동적으로 이미지 변수 할당
foreach ($images as $index => $imgTag) {
    $photoIndex = $index + 1;
    ${"photo$photoIndex"} = $imgTag;
}

// 특정 키워드 이미지들을 별도의 변수에 할당
$photo_check = $additionalImages['checkIn_info'][0] ?? '';
$photo_point = $additionalImages['point'][0] ?? '';
$photo_hotelName = $additionalImages['hotelName'][0] ?? '';
$photo_service = $additionalImages['service'][0] ?? '';

// 멘트1
$comments1 = [
    "안녕하세요, 여행을 좋아하는 여행좋아 블로그 입니다~ 오늘은 {$cityName}의 아름다운 호텔 찐 후기를 소개합니다.",
    "반갑습니다! 여행의 모든 것, 여행좋아 블로그에 오신 것을 환영합니다. 이번 포스트에서는 {$cityName} 여행 중 발견한 숨은 보석 같은 호텔을 알려드릴게요.",
    "여행좋아 블로그 방문을 환영합니다! {$cityName}으로의 여행 계획 중이신가요? 저희가 발견한 멋진 호텔 정보를 공유하려고 해요.",
    "안녕하세요! 여행에 관한 모든 것, 여행좋아 블로그입니다. 오늘은 {$cityName}에서의 환상적인 숙박 경험을 소개해 드립니다.",
    "여행좋아 블로그에 오신 것을 진심으로 환영합니다! 오늘은 {$cityName} 여행자들을 위한 특별한 호텔 리뷰를 준비했습니다.",
    "안녕하세요, 여행좋아 블로그입니다! {$cityName} 여행을 계획 중이신 분들을 위한 꿀팁, 바로 이 호텔 후기를 놓치지 마세요.",
    "여행좋아 블로그를 찾아주셔서 감사합니다~ {$cityName}의 숨겨진 호텔 보석, 지금 바로 소개해 드립니다.",
    "반가워요, 여행좋아 블로그입니다! {$cityName} 여행에 최적화된 호텔 추천 정보, 지금 시작합니다.",
    "안녕하세요! 여행을 사랑하는 모든 분들을 위한 여행좋아 블로그입니다. 오늘은 {$cityName}의 매력적인 호텔 후기를 가져왔어요.",
    "여행좋아 블로그에 오신 걸 환영해요! {$cityName} 여행 계획에 꼭 필요한 호텔 정보, 저희가 준비했습니다."
];

// 멘트2
$comments2 = [
    "{$hotelName}은 {$cityName} 중심부에 위치하여 모든 주요 관광지에 쉽게 접근할 수 있습니다. 이 호텔의 모든 정보를 직접 발굴해서 모아보았는데요, 제 블로그에 와주신 여행을 준비하는 분들에게 도움이 되었으면 좋겠습니다^^",
    "{$hotelName}는 {$cityName}의 아름다운 전경을 자랑하며, 여행자들에게 최고의 휴식처를 제공합니다. 이곳의 숨겨진 매력을 여러분과 나누고자 해요!",
    "{$hotelName}은 편안함과 품격을 동시에 제공하는 {$cityName}의 명소입니다. 저희 블로그를 통해 이 호텔의 매력을 소개하게 되어 기쁩니다.",
    "{$hotelName}는 {$cityName} 방문객들에게 완벽한 숙박 경험을 선사합니다. 저도 직접 경험해 보고, 이곳의 진정한 가치를 알려드리고자 합니다.",
    "저희 블로그를 통해 {$hotelName}의 숨은 이야기를 {$cityName} 여행 준비 중인 여러분께 공유하게 되어 영광입니다. 이 호텔은 정말 특별한 경험을 제공해요.",
    "{$hotelName}에서의 숙박은 {$cityName} 여행의 하이라이트가 될 것입니다. 이곳의 특별한 순간들을 저희 블로그에서 소개해 드리겠습니다.",
    "{$hotelName}은 {$cityName}의 숨겨진 보물과도 같은 곳입니다. 여행의 모든 순간을 특별하게 만들어 줄 이 호텔의 정보를 모아봤어요.",
    "여러분의 {$cityName} 여행을 더욱 풍부하게 해줄 {$hotelName}의 모든 것! 저희 블로그가 여러분께 최고의 정보를 제공할 수 있기를 바랍니다.",
    "{$hotelName}는 {$cityName}에서의 단 하나뿐인 숙박 경험을 약속합니다. 이 호텔의 매력을 여러분과 공유하게 되어 정말 기쁩니다.",
    "{$hotelName}과 {$cityName}의 조화는 여행자들에게 잊지 못할 추억을 선사합니다. 이 호텔의 모든 매력적인 면을 저희 블로그에서 발견하세요."
];

// 멘트3
$comments3 = [
    "이번엔 편의 시설 및 서비스에 대한 정보를 제공해드릴게요, 헬스장과 주차장 그리고 스파까지! 여러 가지 정보를 한눈에 확인할 수 있어요.",
    "여러분에게 이 호텔의 다양한 편의 시설과 서비스를 소개합니다. 고급 스파부터 넓은 주차장, 최신 헬스장까지, 모두 여기에 있습니다.",
    "이 호텔에서 제공하는 서비스와 편의시설에 대해 알려드립니다. 스파, 헬스장, 주차장 등의 정보를 쉽게 찾아볼 수 있어요.",
    "헬스장, 스파, 주차장을 포함한 이 호텔의 편의시설 및 서비스를 확인해 보세요. 여러분의 편안한 숙박을 위해 모든 것이 준비되어 있습니다.",
    "이 호텔의 특별한 편의 시설과 서비스를 소개합니다: 넓은 주차 공간, 현대적인 헬스장, 편안한 스파 등을 이용해 보세요.",
    "주차장, 헬스장, 스파와 같은 이 호텔의 편의시설 및 서비스에 대한 정보를 제공합니다. 여행 중에도 편리함을 누려보세요.",
    "편의 시설과 서비스로 가득한 이 호텔을 소개합니다. 헬스장에서 운동을 하거나 스파에서 휴식을 취하며, 주차장도 넉넉하게 이용할 수 있어요.",
    "이 호텔은 헬스장, 주차장, 스파 등 다양한 편의시설과 서비스를 제공하여 여러분의 여행을 더욱 편안하게 만들어 줍니다.",
    "여러분의 편의를 위한 다양한 시설과 서비스를 준비했습니다. 이 호텔에서는 넓은 주차장, 최신식 헬스장, 고급 스파를 이용할 수 있어요.",
    "이 호텔의 서비스와 편의시설을 소개해 드릴게요. 헬스장, 주차장, 스파 등, 여러분의 숙박을 더욱 특별하게 만들어 줄 모든 것을 한데 모았습니다."
];

// 멘트4
$comments4 = [
    "이번엔 이 도시의 인기 명소, 숙소 근처의 명소 그리고 가까운 공항, 가까운 버스 정거장이나 기차역, 편의점 그리고 여행에 필수인 현금 인출기 ATM 정보까지 다양한 꿀 정보들을 정리해드릴게요.",
    "여행 계획에 꼭 필요한 정보들을 모았습니다. 이 도시의 주요 명소부터, 숙소 인근의 숨겨진 보석, 그리고 교통 편의 시설에 이르기까지, 여러분이 알아야 할 모든 것이 여기 있어요.",
    "이번 여행에서 놓쳐서는 안 될 이 도시의 명소들, 숙소 근처의 특별한 장소들, 그리고 여행의 편리를 도와줄 교통 시설과 ATM 정보까지, 여러분의 여행을 완벽하게 만들 정보를 준비했습니다.",
    "이 도시 탐험을 위한 필수 정보 가이드를 제공해드립니다. 인기 있는 명소, 숙소 주변의 관광지, 그리고 편의점과 ATM까지, 여행에 필요한 모든 정보를 한데 모았어요.",
    "여러분의 여행을 더욱 풍부하게 만들어 줄 정보들을 모았습니다. 이 도시의 인기 명소, 숙소 근처의 관광지, 주요 교통 연결 지점, 그리고 일상 편의 시설에 대한 모든 것을 알려드릴게요.",
    "도시 탐험에 필요한 모든 정보를 여기서 찾아보세요. 인기 명소부터 숙소 인근의 숨은 장소, 교통 수단, 편의점, 그리고 ATM 위치까지, 여행 준비에 완벽한 가이드를 제공합니다.",
    "이번 여행에서 꼭 방문해야 할 명소, 숙소 주변의 특색 있는 장소들, 그리고 여행 중 생활 편의를 위한 교통 정보와 ATM 위치 등을 포함한 꿀팁들을 소개해드립니다.",
    "여행을 계획하면서 꼭 알아야 할 이 도시의 명소, 숙소 근처의 추천 장소들, 그리고 여행에 필수적인 편의 시설 정보까지, 모든 것을 여기에 담았습니다.",
    "이 도시에서의 여행이 더 쉽고 즐거워지도록, 주요 관광지, 숙소 인근의 명소, 그리고 이동을 용이하게 하는 교통 수단과 편의 시설 정보를 정리했습니다.",
    "여행자를 위한 최고의 정보만을 선별했습니다. 이 도시의 빼놓을 수 없는 명소들, 숙소 근처의 숨은 보물, 이동을 위한 교통 편의 시설, 그리고 급할 때 필요한 편의점과 ATM까지, 모두 여러분의 여행에 도움이 될 정보들입니다."
];

//멘트5
$comments5 = [
    "이번엔 이 호텔에 자주 묻는 질문과 답변들을 준비했어요, 호텔에서 직접 제공한 정보이니 신뢰도가 높고 실제 투숙객이 작성한 리뷰들도 있으니 끝까지 확인해주세요.",
    "여러분의 궁금증을 해소시켜드릴 자주 묻는 질문들과 호텔에서 제공하는 답변을 모았습니다. 또한, 이곳을 방문한 사람들의 솔직한 리뷰도 준비되어 있어요!",
    "호텔 선택에 도움이 될 자주 묻는 질문과 그에 대한 답변, 그리고 이전 투숙객들의 리뷰를 한데 모았습니다. 이 모든 정보는 호텔로부터 제공받았으니 안심하세요.",
    "이 호텔과 관련된 자주 묻는 질문들과 호텔 측에서 제공한 답변을 확인해보세요. 더불어, 진정한 투숙객 리뷰도 있으니 참고하시길 바랍니다.",
    "호텔 이용에 앞서 궁금한 점들을 정리해봤습니다. 자주 묻는 질문과 답변, 실제 투숙객의 리뷰를 통해 이 호텔에 대해 더 잘 알아갈 수 있어요.",
    "이 호텔에서 제공하는 FAQ와 실제 투숙객 리뷰를 준비했습니다. 이 정보들은 여러분의 여행 계획에 큰 도움이 될 거예요, 꼭 확인해보세요!",
    "호텔 이용 전 알아두면 좋을 정보들을 모았습니다. 자주 묻는 질문과 호텔 측의 답변, 그리고 이전 방문객들의 리뷰를 참고해 보세요.",
    "여러분이 호텔에 대해 가질 수 있는 모든 질문과 그 답변을 준비했습니다. 호텔에서 직접 제공한 내용과 투숙객 리뷰를 통해 신뢰할 수 있는 정보를 얻으세요.",
    "이 호텔의 자주 묻는 질문과 호텔 측에서 준비한 답변을 확인해보세요. 실제 투숙 경험을 바탕으로 한 리뷰도 있으니, 여행 계획에 꼭 활용해보세요.",
    "호텔에서 직접 제공한 자주 묻는 질문과 답변, 그리고 실제 투숙객들의 리뷰를 모았습니다. 이 정보들로 여러분의 여행 준비를 더욱 완벽하게 해보세요."
];

// 멘트6
$comments6 = [
    "{$hotelName}에서의 경험이 여러분의 {$cityName} 여행을 더욱 특별하게 만들기를 바라구요!!! 호텔의 더 상세한 정보는 아래 링크를 클릭하면 바로가기 슝~~~ 이동해서 확인해보세요 !",
    "여러분의 {$cityName} 방문이 잊지 못할 추억으로 남길 바라며, {$hotelName}에 대한 모든 정보는 여기 링크를 통해 확인 가능합니다! 지금 바로 확인해보세요~",
    "{$hotelName}과 함께하는 {$cityName} 여행이 기대되시나요? 더 많은 정보와 예약 방법은 이 링크를 클릭해서 바로 확인해보세요. 여행을 더 풍부하게 만들어 줄 정보가 가득!",
    "이번 {$cityName} 여행에서 {$hotelName}을 선택하신다면, 후회 없는 여행이 될 거예요! 호텔 예약과 관련된 자세한 정보는 아래 링크를 클릭해주세요. 모험을 시작하세요~",
    "{$hotelName}에서의 숙박이 여러분의 {$cityName} 여행에 특별한 순간들을 더해줄 것입니다. 자세한 정보와 예약은 아래 링크를 통해! 망설이지 말고 바로 확인해보세요.",
    "{$cityName} 여행을 계획 중이시라면, {$hotelName}이 완벽한 선택일 거예요! 더 많은 정보를 원하신다면, 아래 링크를 클릭하셔서 자세히 알아보세요. 여행의 모든 것을 준비했습니다!",
    "여행의 모든 순간을 특별하게 만들어 줄 {$hotelName}, {$cityName}에서의 경험을 더욱 풍부하게 할 정보가 여기에! 아래 링크를 클릭하고 더 많은 것을 발검해보세요.",
    "{$hotelName}에서의 멋진 숙박이 여러분을 기다립니다. {$cityName} 여행의 모든 것, 이 링크를 클릭해서 바로 확인하실 수 있어요. 지금 바로 여행을 떠나볼까요?",
    "여러분의 {$cityName} 여행이 더욱 완벽해지길 바라며, {$hotelName}에 대한 상세 정보와 예약 방법은 아래 링크를 통해 확인해보세요. 여행 준비, 여기서 시작하세요!",
    "{$hotelName}을 방문해야 하는 이유가 궁금하신가요? {$cityName} 여행의 최고의 순간을 제공할 이 호텔에 대한 모든 정보는 아래 링크에서 확인하세요. 링크 클릭 후 바로 확인해 보세요!",
    "여행 계획에 {$hotelName}을 포함시키세요! {$cityName}에서의 경험을 놓치지 마시고, 호텔에 대한 더 많은 정보를 얻기 위해 아래 링크를 클릭하세요. 여러분의 여행이 더욱 특별해질 거예요!"
];

// 랜덤하게 각 섹션에서 문장 선택
$comment1 = $comments1[array_rand($comments1)];
$comment2 = $comments2[array_rand($comments2)];
$comment3 = $comments3[array_rand($comments3)];
$comment4 = $comments4[array_rand($comments4)];
$comment5 = $comments5[array_rand($comments5)];
$comment6 = $comments6[array_rand($comments6)];

//어필리 에이트
$url = "https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid=1729890&hid=".$row["hotel_id"];
$urlec = urlencode($url);

//가격
$hotelPrice = number_format((float)$row['price'], 0);

// 상세평점
$reviewScoresStr = ''; // 이 부분이 추가됨
$reviewScores = json_decode($row['review_scores'], true);
if ($reviewScores === null) {
    $reviewScoresStr .= '<p><strong>' . htmlspecialchars($row['review_scores']) . '</strong></p>';
} else {
    $reviewScoresStr .= '<div class="review-table" style="width: 60%; margin: auto;">'; 
    $reviewScoresStr .= '<table style="width: 100%; border-collapse: collapse;">';
    $reviewScoresStr .= '<caption style="caption-side: top; font-size: larger; font-weight: bold; margin-bottom: 10px;">투숙객 상세 평점 모음</caption>';
    foreach ($reviewScores as $category => $score) {
        $reviewScoresStr .= '<tr>';
        $reviewScoresStr .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($category) . '</td>';
        $reviewScoresStr .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . htmlspecialchars($score) . '</td>';
        $reviewScoresStr .= '</tr>';
    }
    $reviewScoresStr .= '</table>';
    $reviewScoresStr .= '</div>';
}

// 'children' 필드가 비어있지 않은 경우에만 문자열 생성
if (!empty($row['children'])) {
  $childrenFacilityStr = "<p><strong>아동을 위해 구비된 시설:</strong> {$row['children']}</p>";
}

// 장애인 편의시설 관련
$accessibilityText = str_replace("장애인 접근 편의 관련: ", "", $row['accessibility']);
$accessibilityStr = '';
if (!empty($accessibilityText)) {
    $accessibilityStr = "<p><strong>장애인 편의시설:</strong> {$accessibilityText}</p>";
}

// 리뷰 개수 관련 HTML 문자열 초기화
$reviewCountStr = '';
if (!empty($row['review_count'])) {
    $reviewCountStr = "<p><strong>리뷰갯수:</strong> {$row['review_count']}</p>";
}

// 호텔 소개
$hotelMessageStr = '';
// 'hotel_message'가 비어있지 않은 경우
if (!empty($row['hotel_message'])) {
    $hotelMessage = $row['hotel_message'];
    // 단어 단위로 분리
    $words = explode(' ', $hotelMessage);
    // 랜덤 단어 3개에 <strong> 태그 적용
    $randomWordKeysForStrong = array_rand($words, min(3, count($words)));
    foreach ((array)$randomWordKeysForStrong as $key) {
        $words[$key] = "<strong>{$words[$key]}</strong>";
    }
    // 랜덤 단어 2개에 <b> 태그 적용 (이미 <strong>이 적용된 단어는 제외)
    $remainingWords = array_diff_key($words, array_flip((array)$randomWordKeysForStrong)); // <strong>이 적용된 단어 제외
    $randomWordKeysForBold = array_rand($remainingWords, min(2, count($remainingWords)));
    foreach ((array)$randomWordKeysForBold as $key) {
        if (!in_array($key, (array)$randomWordKeysForStrong)) { // 이미 <strong>이 적용된 단어는 제외
            $words[$key] = "<b>{$words[$key]}</b>";
        }
    }
    $modifiedHotelMessage = implode(' ', $words);

    $hotelMessageStr = <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; background: #ffffff; border: 1px solid gold; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="background-color: #FFD700; color: #000; padding: 20px; font-size: 20px; font-weight: bold; border-bottom: 2px solid gold;">
            호텔에서 제공하는 소개 글
        </div>
        <div style="padding: 20px; line-height: 1.6; color: #333;">
            {$photo3}
            {$modifiedHotelMessage}
        </div>
    </div>
HTML;
}

// 호텔 설명
$hotelDescStr = '';
if (!empty($row['hotel_desc'])) {
    $words = explode(' ', $row['hotel_desc']);
    $randomWordKeys = array_rand($words, min(3, count($words))); 
    // 첫 2개 단어에 <strong> 태그 적용
    foreach ($randomWordKeys as $i => $key) {
        if ($i < 2) {
            $words[$key] = "<strong>{$words[$key]}</strong>";
        } else {
            // 마지막 단어에 <u> 태그 적용
            $words[$key] = "<u>{$words[$key]}</u>";
            break; // 하나의 단어에만 적용
        }
    }
    $modifiedText = implode(' ', $words);
    $sentences = explode('. ', $modifiedText);
    if (count($sentences) > 1) {
        $randomSentenceKey = array_rand($sentences); 
        $sentences[$randomSentenceKey] = "<span style='background-color: #808080; color: #FFFFFF;'>{$sentences[$randomSentenceKey]}.</span>";
    }
    $modifiedHotelDesc = implode('. ', $sentences);
    $hotelDescStr = <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; background: #ffffff; border: 1px solid gold; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="background-color: #FFD700; color: #000; padding: 20px; font-size: 20px; font-weight: bold; border-bottom: 2px solid gold;">
            호텔 설명
        </div>
        <div style="padding: 20px; line-height: 1.6; color: #333;">
            {$photo2}
            {$modifiedHotelDesc}
        </div>
    </div>
HTML;
// 'hotel_desc'가 비어있는 경우
} else {
    $cityRecommendationTitle = $row['city'] . " 추천호텔";
    $hotelDescStr = <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; background: #ffffff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="background-color: #FFD700; color: #000; padding: 20px; font-size: 20px; font-weight: bold; border-bottom: 1px solid #ddd;">
            {$cityRecommendationTitle}
        </div>
        <div style="padding: 20px; line-height: 1.6; color: #333;">
            {$photo2}
        </div>
    </div>
HTML;
}


//숙소 일반 정보
$checkinTimeInfoStr = '';
if (!empty($row['checkin_time_info'])) {
    $checkinTimeInfoStr = <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; background: #ffffff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="background-color: #FFD700; color: #000; padding: 20px; font-size: 20px; font-weight: bold; border-bottom: 1px solid #ddd;">
            숙소 일반 정보
        </div>
        <div style="padding: 20px; line-height: 1.6; color: #333;">
            {$row['checkin_time_info']}
        </div>
    </div>
HTML;
}

//국가 도시 호텔명 주소
$tableContent = <<<HTML
<div>
<table style="width: 100%; border-collapse: collapse;">
<tr style="background-color: #f2f2f2;"><th style="border: 1px solid #ddd; padding: 8px; text-align: left;">호텔 제공 분류</th><th style="border: 1px solid #ddd; padding: 8px; text-align: left;">정보</th></tr>
<tr><td style="border: 1px solid #ddd; padding: 8px;">국가명</td><td style="border: 1px solid #ddd; padding: 8px;">{$row['country']}</td></tr>
<tr style="background-color: #f2f2f2;"><td style="border: 1px solid #ddd; padding: 8px;">도시명</td><td style="border: 1px solid #ddd; padding: 8px;">{$row['city']}</td></tr>
<tr><td style="border: 1px solid #ddd; padding: 8px;">호텔 이름</td><td style="border: 1px solid #ddd; padding: 8px;">{$row['hotel_translated_name']}</td></tr>
<tr style="background-color: #f2f2f2;"><td style="border: 1px solid #ddd; padding: 8px;">Address</td><td style="border: 1px solid #ddd; padding: 8px;">{$row['address']}</td></tr>
<tr><td style="border: 1px solid #ddd; padding: 8px;">1박 최소 금액</td><td style="border: 1px solid #ddd; padding: 8px;">${hotelPrice}원</td></tr>
</table>
</div>
HTML;

//체크인 정보
$checkTimeInfo = json_decode($row['check_time_info'], true);
$checkTimeInfoHtml = '';
if (!empty($checkTimeInfo)) {
    foreach ($checkTimeInfo as $key => $value) {
        if (!empty($value)) {
            $checkTimeInfoHtml .= "<p><strong>$key:</strong> $value</p>";
        }
    }
}
if (empty($checkTimeInfoHtml)) {
    $checkTimeInfoHtml = "<p style='text-align: center; font-family: Arial, sans-serif; font-weight: bold; max-width: 600px; margin: 20px auto; padding: 20px;'>이 호텔의 체크인 정보는 홈페이지를 참고해주세요.</p>";
} else {
    $checkTimeInfoHtml = "<div class='check-time-info' style='font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; background: #f9f9f9; border: 1px solid gold; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;'>
    <h2 style='color: #333;'>체크인 / 체크아웃 정보</h2>
    $checkTimeInfoHtml
    </div>";
}

// 어린이, 장애인시설, 리뷰숫자, 평점
$facilityTableContent = "";
$facilityRows = '';
//어린이 시설
if (!empty($row['children'])) {
    $facilityRows .= "<tr><td style='border: 1px solid #ddd; padding: 8px; background-color: #f9f9f9;'>아동을 위해 구비된 시설</td><td style='border: 1px solid #ddd; padding: 8px;'>{$row['children']}</td></tr>";
}
// 장애인 시설 
if (!empty($row['accessibility'])) {
    $facilityRows .= "<tr><td style='border: 1px solid #ddd; padding: 8px; background-color: #f9f9f9;'>장애인 편의시설</td><td style='border: 1px solid #ddd; padding: 8px;'>{$row['accessibility']}</td></tr>";
}
//리뷰갯수 
if (!empty($row['review_count'])) {
    $formattedReviewCount = number_format($row['review_count']) . '개';
    $facilityRows .= "<tr><td style='border: 1px solid #ddd; padding: 8px; background-color: #f9f9f9;'>리뷰갯수</td><td style='border: 1px solid #ddd; padding: 8px;'>{$formattedReviewCount}</td></tr>";
}
//평가점수
if (!empty($row['rating'])) {
    $formattedRating = $row['rating'] . '점';
    $facilityRows .= "<tr><td style='border: 1px solid #ddd; padding: 8px; background-color: #f9f9f9;'>평가점수</td><td style='border: 1px solid #ddd; padding: 8px;'>{$formattedRating}</td></tr>";
}
if (!empty($facilityRows)) {
    $facilityTableContent = <<<HTML
    <div>
    <table style="width: 100%; border-collapse: collapse; border: 2px solid #007BFF; margin-top: 20px;">
    <tr style="background-color: #007BFF; color: #ffffff;"><th style="border: 1px solid #ddd; padding: 12px; text-align: left;">호텔 시설</th><th style="border: 1px solid #ddd; padding: 12px; text-align: left;">정보</th></tr>
    $facilityRows
    </table>
    </div>
    HTML;
}

// 성급, 피드백, 숙소에서 사용하는 언어 및 서비스 제공, 호텔 서비스에 대한 표 생성
$additionalInfoTableContent = "";
$additionalInfoRows = '';
if (!empty($row['star_rating'])) {
    $additionalInfoRows .= "<tr><td>성급</td><td>{$row['star_rating']}</td></tr>";
}
if (!empty($row['feedback'])) {
    $additionalInfoRows .= "<tr><td>피드백</td><td>{$row['feedback']}</td></tr>";
}
if (!empty($row['languages'])) {
    $additionalInfoRows .= "<tr><td>숙소에서 사용하는 언어</td><td>{$row['languages']}</td></tr>";
}
// '서비스 제공'과 '호텔 서비스' 행 추가
if (!empty($row['entry'])) {
    $additionalInfoRows .= "<tr><td>서비스 제공</td><td>{$row['entry']}</td></tr>";
}
if (!empty($row['hotel_service'])) {
    $additionalInfoRows .= "<tr><td>호텔 서비스</td><td>{$row['hotel_service']}</td></tr>";
}

if (!empty($additionalInfoRows)) {
    $additionalInfoTableContent = <<<HTML
    <div>
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #009879;">
    <thead>
        <tr style="background-color: #009879; color: white; font-family: Arial, sans-serif;">
            <th colspan="2" style="padding: 12px 15px; text-align: left;">호텔의 다양한 서비스</th>
        </tr>
    </thead>
    <tbody>
        $additionalInfoRows
    </tbody>
    </table>
    </div>
    HTML;
}

//장소 정보
$placeInfo = json_decode($row['place_info'], true);
$placeInfoHtml = '';
if (!empty($placeInfo)) {
    $placeInfoHtml .= "<div class='place-info' style='font-family: Arial, sans-serif; background-color: #000; color: #fff; max-width: 800px; margin: 20px auto; border-radius: 8px; padding: 20px;'>";
    foreach ($placeInfo as $category => $items) {
        $placeInfoHtml .= "<h2 style='border-bottom: 1px solid #fff; padding-bottom: 10px;'>$category</h2>";
        $placeInfoHtml .= "<ul>";
        foreach ($items as $item) {
            $placeInfoHtml .= "<li>$item</li>";
        }
        $placeInfoHtml .= "</ul>";
    }
    $placeInfoHtml .= "</div>";
} else {
    $placeInfoHtml = "<p style='text-align: center; background-color: #000; color: #fff; padding: 20px;'>홈페이지를 참고해주세요.</p>";
}

// 자주 묻는 질문답변, FAQ
$faqsHtml = '';
if (!empty($row['faqs'])) {
    $faqs = json_decode($row['faqs'], true);
    if (!empty($faqs)) {
        $faqsHtml = "<div class='faqs' style='font-family: Noto Sans, sans-serif; background-color: #f0f4f8; color: #333; max-width: 800px; margin: 20px auto; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
        // '자주 묻는 질문 모음' 소제목 추가
        $faqsHtml .= "<h2 style='text-align: center; margin-bottom: 20px; color: black;'>자주 묻는 질문 모음</h2>";
        foreach ($faqs as $faq) {
            $faqsHtml .= "<div class='faq-item' style='margin-bottom: 15px;'>";
            $faqsHtml .= "<h3 style='color: #007BFF;'>{$faq['question']}</h3>";
            $faqsHtml .= "<p>{$faq['answer']}</p>";
            $faqsHtml .= "</div>";
        }
        $faqsHtml .= "</div>";
    }
}
if (empty($faqsHtml)) {
    $faqsHtml = "<p style='text-align: center; font-family: Noto Sans, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>홈페이지를 참고해주세요.</p>";
}


// 리뷰 내용
$reviewsJson = $row['reviews'];
$reviews = json_decode($reviewsJson, true);
$reviewsContent = '';

if (!empty($reviews) && is_array($reviews)) {
    $reviewsContent .= "<div style='font-family: Noto Sans, sans-serif; max-width: 800px; margin: 20px auto; background: #f0f0f0; padding: 20px; border-radius: 8px;'>";
    $reviewsContent .= "<h2 style='text-align: center; font-weight: bold; color: #333; margin-bottom: 20px;'>실제 리뷰 모음</h2>";

    foreach ($reviews as $review) {
        // 리뷰의 구조가 예상대로인지 확인하고, 필요한 키가 있는 경우 처리
        if (is_array($review) && isset($review['title']) && isset($review['bodyText'])) {
            $title = htmlspecialchars($review['title']);
            $bodyText = htmlspecialchars($review['bodyText']);
            $reviewsContent .= "<div style='margin-bottom: 20px; padding: 10px; background: #ffffff; border: 1px solid gold; border-radius: 4px;'>";
            $reviewsContent .= "<h3 style='color: #333;'>$title</h3>";
            $reviewsContent .= "<p style='color: #666;'>$bodyText</p>";
            $reviewsContent .= "</div>";
        } else {
            // 키가 없는 경우 '신규호텔입니다' 메시지 추가
            $reviewsContent .= "<div style='margin-bottom: 20px; padding: 10px; background: #ffffff; border: 1px solid gold; border-radius: 4px;'>";
            $reviewsContent .= "<h3 style='color: #333;'>신규호텔입니다</h3>";
            $reviewsContent .= "</div>";
        }
    }
    $reviewsContent .= "</div>";
} else {
    $reviewsContent = "<p style='text-align: center; font-family: Noto Sans, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>리뷰가 없습니다.</p>";
}



// $photo1 이미지 태그를 <div>로 감싸기, 썸네일 이미지로 본문엔 display none 처리
$photo1 = "<div id='thumbnail' style='display: none;'>{$photo1}</div>";



// html은 여기서 조정하면됩니다 
$postContent = <<<HTML
    {$photo1}
    {$photo2}
    <div class='comment'>{$comment1}</div>
    <div class='comment'>{$comment2}</div>
    {$photo_point}
    {$photo_hotelName}
    {$photo_check}
    <div class='comment'>{$comment3}</div>
    {$photo_service}
    {$photo4}
    {$tableContent}
    {$photo5}
    {$reviewScoresStr}
    {$photo6}
    {$facilityTableContent}
    {$photo7}
    {$additionalInfoTableContent}
    {$hotelMessageStr}
    {$hotelDescStr}
    {$photo10}
    {$checkinTimeInfoStr}
    {$checkTimeInfoHtml}
    {$photo8}
    <div class='comment'>{$comment4}</div>
    {$placeInfoHtml}
    <div class='comment'>{$comment5}</div>
    {$faqsHtml}
    {$photo9}
    {$reviewsContent}
    <div class='comment'>{$comment6}</div>
    <div><a class='link-btn' href='https://newtip.net/click.php?m=agoda&a={$affiliateId}&l=9999&l_cd1=3&l_cd2=0&tu=$urlec' target='_blank'>호텔 상세보기</a></div>
HTML;

// 포스팅 제목을 위한 배열 정의
$postTitles = [
  $row['country'] . "의 " . $row['city'] . "의 아름다운 호텔",
  $row['country'] . "의 " . $row['city'] . "의 멋진 호텔",
  $row['country'] . "의 " . $row['city'] . "의 고급 호텔",
  $row['country'] . "의 " . $row['city'] . "의 편안한 호텔",
  $row['country'] . "의 " . $row['city'] . "의 저렴한 호텔"
];

// 배열에서 랜덤하게 하나의 제목 선택
$randomTitleKey = array_rand($postTitles);
$randomTitle = $postTitles[$randomTitleKey];

// 포스트 추가를 위한 wp_insert_post 함수 사용
$newPost = [
  'post_title'    => $randomTitle, // 랜덤하게 선택된 제목 사용
  'post_content'  => $postContent,
  'post_status'   => 'publish',
  'post_author'   => 1,
  'post_type'     => 'post',
];

        // 포스트를 데이터베이스에 추가
        $postId = wp_insert_post($newPost);

        // 포스트가 성공적으로 추가되면, 'posted' 플래그 업데이트
        if ($postId) {
            // 'posted' 플래그 업데이트
            $updateQuery = "UPDATE sweden SET posted = 1 WHERE hotel_id = " . $row['hotel_id'];
            $conn->query($updateQuery);
        }
    }
} else {
    echo "포스팅할 새로운 데이터가 없습니다.";
}

// 데이터베이스 접속 종료
$conn->close();

// 실행 시간 및 메모리 사용량 측정
$endTime = microtime(true);
$executionTime = ($endTime - $startTime);
$peakMemoryUsage = memory_get_peak_usage(true);

// 실행 시간 및 메모리 사용량 출력
echo "실행 시간: " . $executionTime . " 초<br>";
echo "최대 메모리 사용량: " . ($peakMemoryUsage / 1024 / 1024) . " MB<br>";

// 로그 파일에 기록
$logMessage = "실행 시간: " . $executionTime . " 초, 최대 메모리 사용량: " . ($peakMemoryUsage / 1024 / 1024) . " MB\n";
file_put_contents('autopost_log.txt', $logMessage, FILE_APPEND);
?>
