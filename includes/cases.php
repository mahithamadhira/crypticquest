<?php
// Cryptic Quest - Case Data Structure

// Function to load user data from JSON file into session using helper function
function loadUserDataIntoSession($username) {
    $userCases = [];
    $totalScore = 0;
    
    $usersData = readJsonFile(USERS_FILE);
    foreach ($usersData as $user) {
        if ($user['username'] === $username) {
            foreach ($user['cases_completed'] as $case) {
                $caseData = getCase($case['case_id']);
                $userCases[] = [
                    'case_id' => $case['case_id'],
                    'title' => $caseData ? $caseData['title'] : 'Unknown Case',
                    'score' => (int)$case['score'],
                    'completed_at' => strtotime($case['completed_at']) ?: time(),
                    'time_taken' => (int)$case['time_taken'],
                    'correct' => $case['status'] === 'solved'
                ];
                if ($case['status'] === 'solved') {
                    $totalScore += (int)$case['score'];
                }
            }
            break;
        }
    }
    
    $_SESSION['cases_completed'] = $userCases;
    $_SESSION['total_score'] = $totalScore;
    
    return ['cases' => $userCases, 'total_score' => $totalScore];
}

// Case difficulty levels
define('DIFFICULTY_BEGINNER', 'beginner');
define('DIFFICULTY_INTERMEDIATE', 'intermediate'); 
define('DIFFICULTY_ADVANCED', 'advanced');
define('DIFFICULTY_EXPERT', 'expert');

// Case categories
define('CATEGORY_THEFT', 'theft');
define('CATEGORY_MURDER', 'murder');
define('CATEGORY_FRAUD', 'fraud');
define('CATEGORY_CONSPIRACY', 'conspiracy');

// Sample cases data structure
function getCases() {
    return [
        'missing_heirloom' => [
            'id' => 'missing_heirloom',
            'title' => 'The Missing Heirloom',
            'description' => 'A valuable diamond necklace worth $50,000 has vanished from Mrs. Pemberton\'s locked bedroom safe.',
            'difficulty' => DIFFICULTY_BEGINNER,
            'category' => CATEGORY_THEFT,
            'estimated_time' => '25-35 min',
            'points' => 300,
            'scenario' => 'The wealthy Mrs. Pemberton\'s prized family heirloom, a diamond necklace worth $50,000, has vanished from her locked bedroom safe. The theft occurred when she briefly left her bedroom. James the butler, Sarah the maid, and Tom the gardener all had access to the house during this time.',
            'suspects' => [
                'james_butler' => 'James Butler',
                'sarah_maid' => 'Sarah Maid', 
                'tom_gardener' => 'Tom Gardener'
            ],
            'evidence' => [
                'threatening_letter' => [
                    'id' => 'threatening_letter',
                    'name' => 'Threatening Letter',
                    'description' => 'A crumpled letter found in the trash threatening financial consequences.',
                    'content' => 'Dear Mrs. Pemberton, You have something that belongs to me. The family jewels should be returned, or there will be serious consequences. - A concerned party',
                    'discovery_text' => 'Found crumpled in the wastebasket.',
                    'relevance' => 'high'
                ],
                'muddy_footprints' => [
                    'id' => 'muddy_footprints',
                    'name' => 'Muddy Footprints',
                    'description' => 'Fresh muddy boot prints leading from the garden to the bedroom.',
                    'content' => 'Size 11 boot prints with distinctive tread pattern match Tom\'s work boots.',
                    'discovery_text' => 'Trail of mud leads directly to the safe area.',
                    'relevance' => 'high'
                ],
                'security_footage' => [
                    'id' => 'security_footage',
                    'name' => 'Security Footage',
                    'description' => 'Surveillance camera shows suspicious movement near the stairs.',
                    'content' => 'Timestamp shows a figure in work clothes ascending the stairs at 11:47 PM.',
                    'discovery_text' => 'Reviewed security system footage.',
                    'relevance' => 'medium'
                ],
                'safe_combination' => [
                    'id' => 'safe_combination',
                    'name' => 'Safe Combination',
                    'description' => 'Evidence about who knew the safe combination.',
                    'content' => 'Mrs. Pemberton claims only she knew the safe combination, but Tom the gardener was seen with the family for 10 years and would have observed her opening the safe.',
                    'discovery_text' => 'Questioned household staff about safe access.',
                    'relevance' => 'high'
                ]
            ],
            'hints' => [
                'Pay attention to who had both opportunity and knowledge of the house layout.',
                'The threatening letter might not be from a stranger - consider who would know about the family jewels.',
                'Physical evidence like footprints can be very telling - match them to the people with access.'
            ],
            'solution' => [
                'primary_suspect' => 'tom_gardener',
                'motive' => 'Financial desperation - Tom correctly identified the motive.',
                'evidence_summary' => 'Strong evidence points to Tom: threatening letter, muddy footprints matching work boots, and his forced entry to the safe.',
                'explanation' => 'Tom was facing serious financial troubles and knew the family routine. He wrote the threatening letter, accessed the bedroom using his knowledge of the house, and forced open the safe using tools from the garden shed.'
            ]
        ],
        
        'night_museum' => [
            'id' => 'night_museum',
            'title' => 'Night at the Museum',
            'description' => 'An ancient Egyptian artifact has gone missing during gallery event. Security footage shows suspicious activity.',
            'difficulty' => DIFFICULTY_INTERMEDIATE,
            'category' => CATEGORY_THEFT,
            'estimated_time' => '35-50 min',
            'points' => 500,
            'scenario' => 'During a prestigious gallery event at the Metropolitan Museum, an ancient Egyptian scarab worth $100,000 disappeared from its display case. Three individuals had special access: Dr. Richards (curator), Ms. Chen (security chief), and Mr. Williams (donor).',
            'suspects' => [
                'dr_richards' => 'Dr. Richards',
                'ms_chen' => 'Ms. Chen',
                'mr_williams' => 'Mr. Williams'
            ],
            'evidence' => [
                'access_logs' => [
                    'id' => 'access_logs',
                    'name' => 'Access Logs',
                    'description' => 'Digital records showing who accessed the Egyptian wing.',
                    'content' => 'Dr. Richards: 6:30 PM, Ms. Chen: 7:15 PM, Mr. Williams: 8:45 PM. Alarm disabled at 8:50 PM.',
                    'discovery_text' => 'Retrieved from security system database.',
                    'relevance' => 'high'
                ],
                'expert_knowledge' => [
                    'id' => 'expert_knowledge',
                    'name' => 'Expert Knowledge',
                    'description' => 'Information about who had expertise to handle the artifact.',
                    'content' => 'Only Dr. Richards has the specialized knowledge to safely remove the scarab without triggering additional sensors.',
                    'discovery_text' => 'Consultation with museum security protocols.',
                    'relevance' => 'high'
                ],
                'financial_records' => [
                    'id' => 'financial_records',
                    'name' => 'Financial Records',
                    'description' => 'Recent financial troubles of museum staff.',
                    'content' => 'Dr. Richards recently filed for bankruptcy due to gambling debts. Needs money urgently.',
                    'discovery_text' => 'Background check revealed financial stress.',
                    'relevance' => 'medium'
                ]
            ],
            'hints' => [
                'Look for someone with both the knowledge to handle artifacts safely and financial motivation.',
                'The timing of the access logs might reveal who had the opportunity during the critical window.',
                'Consider who would know how to disable the artifact\'s additional security sensors.'
            ],
            'solution' => [
                'primary_suspect' => 'dr_richards',
                'motive' => 'Financial desperation due to gambling debts',
                'evidence_summary' => 'Dr. Richards had the expertise, access, and financial motive to steal the artifact.',
                'explanation' => 'Dr. Richards used his curator access and expert knowledge to bypass security systems and safely remove the scarab, planning to sell it to cover gambling debts.'
            ]
        ],
        
        'corporate_conspiracy' => [
            'id' => 'corporate_conspiracy',
            'title' => 'The Corporate Conspiracy',
            'description' => 'Insider trading scandal rocks tech company. Follow the money trail to uncover the truth.',
            'difficulty' => DIFFICULTY_ADVANCED,
            'category' => CATEGORY_FRAUD,
            'estimated_time' => '45-60 min',
            'points' => 750,
            'scenario' => 'TechNova Inc., a major software company, is under investigation for insider trading. Stock prices mysteriously jumped just before a major acquisition announcement. Three executives had advance knowledge: CEO Martin Clarke, CFO Lisa Park, and Head of M&A David Kim.',
            'suspects' => [
                'martin_clarke' => 'Martin Clarke (CEO)',
                'lisa_park' => 'Lisa Park (CFO)',
                'david_kim' => 'David Kim (M&A Head)'
            ],
            'evidence' => [
                'trading_records' => [
                    'id' => 'trading_records',
                    'name' => 'Trading Records',
                    'description' => 'Stock trading activity in the days before the announcement.',
                    'content' => 'Large purchases of TechNova stock were made through an offshore account registered to "M. Clarke Holdings" three days before the acquisition news.',
                    'discovery_text' => 'SEC investigation uncovered suspicious trading patterns.',
                    'relevance' => 'high'
                ],
                'email_communications' => [
                    'id' => 'email_communications',
                    'name' => 'Email Communications',
                    'description' => 'Internal company emails about the acquisition.',
                    'content' => 'Email from Martin Clarke to his brother-in-law: "Now would be a good time to invest in tech stocks, especially ones focused on AI. Trust me on this."',
                    'discovery_text' => 'Found in deleted emails folder on company server.',
                    'relevance' => 'high'
                ],
                'financial_pressure' => [
                    'id' => 'financial_pressure',
                    'name' => 'Financial Pressure',
                    'description' => 'Personal financial situations of the suspects.',
                    'content' => 'Martin Clarke recently took out a $2M mortgage for a new mansion and has two children starting expensive private colleges.',
                    'discovery_text' => 'Background financial investigation.',
                    'relevance' => 'medium'
                ],
                'meeting_minutes' => [
                    'id' => 'meeting_minutes',
                    'name' => 'Board Meeting Minutes',
                    'description' => 'Confidential board meeting discussions.',
                    'content' => 'Minutes show Martin Clarke pushed aggressively for the acquisition timing, arguing for "immediate action" despite Lisa Park\'s concerns about market readiness.',
                    'discovery_text' => 'Subpoenaed from company legal department.',
                    'relevance' => 'medium'
                ]
            ],
            'hints' => [
                'Follow the money trail - look for unusual financial transactions around the announcement date.',
                'Email communications might reveal improper sharing of confidential information.',
                'Consider who had both advance knowledge and personal financial pressure.'
            ],
            'solution' => [
                'primary_suspect' => 'martin_clarke',
                'motive' => 'Personal financial gain through insider trading',
                'evidence_summary' => 'Trading records, email communications, and financial pressure all point to Martin Clarke exploiting his position for personal gain.',
                'explanation' => 'CEO Martin Clarke used his insider knowledge of the acquisition to tip off his brother-in-law, who then traded through an offshore account. Clarke needed quick money for his new mortgage and children\'s education expenses.'
            ]
        ],
        
        'mansion_murder' => [
            'id' => 'mansion_murder',
            'title' => 'Murder at Blackwood Manor',
            'description' => 'Lord Blackwood found dead in his locked study. Three heirs stand to inherit millions.',
            'difficulty' => DIFFICULTY_EXPERT,
            'category' => CATEGORY_MURDER,
            'estimated_time' => '60-90 min',
            'points' => 1000,
            'scenario' => 'Lord Blackwood was found dead in his locked study during a family gathering to discuss his will. The door was locked from the inside, and the only key was in his pocket. Three family members were present: his nephew Charles, his daughter Victoria, and his stepson Marcus.',
            'suspects' => [
                'charles_nephew' => 'Charles Blackwood (Nephew)',
                'victoria_daughter' => 'Victoria Blackwood (Daughter)',
                'marcus_stepson' => 'Marcus Blackwood (Stepson)'
            ],
            'evidence' => [
                'locked_room' => [
                    'id' => 'locked_room',
                    'name' => 'Locked Room Mystery',
                    'description' => 'The study was locked from the inside with no other apparent exit.',
                    'content' => 'Study door locked from inside, key in Lord Blackwood\'s pocket. Windows painted shut for years. Secret passage behind bookshelf leads to wine cellar.',
                    'discovery_text' => 'Thorough examination of the crime scene.',
                    'relevance' => 'high'
                ],
                'poison_analysis' => [
                    'id' => 'poison_analysis',
                    'name' => 'Poison Analysis',
                    'description' => 'Toxicology report on Lord Blackwood\'s death.',
                    'content' => 'Cause of death: Cyanide poisoning. Traces found in his evening brandy glass. Time of death estimated between 9-10 PM.',
                    'discovery_text' => 'Medical examiner\'s autopsy report.',
                    'relevance' => 'high'
                ],
                'will_changes' => [
                    'id' => 'will_changes',
                    'name' => 'Recent Will Changes',
                    'description' => 'Lord Blackwood had recently modified his will.',
                    'content' => 'Will changed one week ago to exclude Marcus completely, leaving everything to Victoria and Charles. Marcus was furious when he discovered this.',
                    'discovery_text' => 'Legal documents from family attorney.',
                    'relevance' => 'high'
                ],
                'alibis_investigation' => [
                    'id' => 'alibis_investigation',
                    'name' => 'Alibis Investigation',
                    'description' => 'Where each suspect was during the estimated time of death.',
                    'content' => 'Charles: Reading in library (staff confirms). Victoria: Taking phone calls in garden (phone records verify). Marcus: Claimed to be in bathroom, but no one saw him for 45 minutes.',
                    'discovery_text' => 'Interviews with all family members and staff.',
                    'relevance' => 'high'
                ],
                'secret_passage' => [
                    'id' => 'secret_passage',
                    'name' => 'Secret Passage Evidence',
                    'description' => 'Evidence found in the hidden passage behind the bookshelf.',
                    'content' => 'Fresh footprints and fabric fibers from Marcus\'s jacket found in secret passage. Passage connects study to wine cellar where Marcus was seen earlier.',
                    'discovery_text' => 'Forensic examination of the secret passage.',
                    'relevance' => 'high'
                ]
            ],
            'hints' => [
                'In a locked room mystery, think about alternative ways to enter and exit the room.',
                'The will changes might have created a powerful motive for revenge.',
                'Physical evidence in unexpected places can reveal the truth about how the impossible was made possible.'
            ],
            'solution' => [
                'primary_suspect' => 'marcus_stepson',
                'motive' => 'Revenge and financial desperation after being written out of the will',
                'evidence_summary' => 'Marcus used the secret passage to access the locked study, poisoned the brandy, and escaped the same way, creating the locked room illusion.',
                'explanation' => 'Marcus discovered he was cut from the will and planned the perfect murder. He used his knowledge of the secret passage (discovered as a child) to enter the locked study, poison Lord Blackwood\'s nightly brandy with cyanide, and escape undetected, making it appear impossible.'
            ]
        ],
        
        'art_gallery_heist' => [
            'id' => 'art_gallery_heist',
            'title' => 'The Gallery Heist',
            'description' => 'A priceless Van Gogh painting stolen during opening night. The thief left no trace.',
            'difficulty' => DIFFICULTY_INTERMEDIATE,
            'category' => CATEGORY_THEFT,
            'estimated_time' => '40-55 min',
            'points' => 600,
            'scenario' => 'During the opening night of a prestigious art exhibition, Van Gogh\'s "Starry Night Over the City" worth $15 million vanished from the main gallery. The theft occurred during a 20-minute power outage. Three individuals had special access: gallery owner Elena Rodriguez, security consultant Jake Morrison, and art critic Amanda Foster.',
            'suspects' => [
                'elena_rodriguez' => 'Elena Rodriguez (Gallery Owner)',
                'jake_morrison' => 'Jake Morrison (Security Consultant)',
                'amanda_foster' => 'Amanda Foster (Art Critic)'
            ],
            'evidence' => [
                'security_footage' => [
                    'id' => 'security_footage',
                    'name' => 'Security Footage',
                    'description' => 'Camera recordings from before and after the power outage.',
                    'content' => 'Footage shows Jake Morrison entering the main gallery 5 minutes before the power outage with a large briefcase. Emergency lighting reveals a figure moving near the Van Gogh display.',
                    'discovery_text' => 'Analysis of security camera recordings.',
                    'relevance' => 'high'
                ],
                'power_outage_timing' => [
                    'id' => 'power_outage_timing',
                    'name' => 'Power Outage Analysis',
                    'description' => 'Investigation into the suspicious power failure.',
                    'content' => 'Power outage was caused by deliberate tampering with the main electrical panel. Only Jake Morrison, as security consultant, had the knowledge and access codes to the electrical room.',
                    'discovery_text' => 'Electrical system investigation by city engineers.',
                    'relevance' => 'high'
                ],
                'insurance_investigation' => [
                    'id' => 'insurance_investigation',
                    'name' => 'Insurance Investigation',
                    'description' => 'Financial motivations behind the theft.',
                    'content' => 'Elena Rodriguez recently increased insurance coverage on the Van Gogh by 200% and is facing bankruptcy due to declining gallery profits.',
                    'discovery_text' => 'Insurance records and financial audit.',
                    'relevance' => 'medium'
                ],
                'expert_authentication' => [
                    'id' => 'expert_authentication',
                    'name' => 'Authentication Records',
                    'description' => 'Recent questions about the painting\'s authenticity.',
                    'content' => 'Amanda Foster published an article questioning the authenticity of several pieces in Elena\'s collection, including the Van Gogh. She had been investigating the painting\'s provenance for months.',
                    'discovery_text' => 'Art world publications and research notes.',
                    'relevance' => 'medium'
                ]
            ],
            'hints' => [
                'Someone with security knowledge would know how to create the perfect conditions for a theft.',
                'The timing of the power outage seems too convenient to be coincidental.',
                'Look for who had both the technical knowledge and opportunity to disable security systems.'
            ],
            'solution' => [
                'primary_suspect' => 'jake_morrison',
                'motive' => 'Financial gain through professional theft ring connections',
                'evidence_summary' => 'Jake Morrison used his security access to plan and execute the heist during a deliberate power outage he caused.',
                'explanation' => 'Jake Morrison, leveraging his position as security consultant, orchestrated the theft by deliberately causing a power outage and using his knowledge of the gallery layout to steal the painting in darkness. He had connections to international art theft rings and planned to sell the piece on the black market.'
            ]
        ],
        
        'campus_mystery' => [
            'id' => 'campus_mystery',
            'title' => 'The Campus Research Theft',
            'description' => 'Breakthrough research data stolen from university lab. Academic rivals under suspicion.',
            'difficulty' => DIFFICULTY_BEGINNER,
            'category' => CATEGORY_THEFT,
            'estimated_time' => '20-30 min',
            'points' => 250,
            'scenario' => 'Dr. Sarah Chen\'s groundbreaking cancer research data has been stolen from her secured university lab. The theft occurred over the weekend when only a few people had building access. Three suspects had both motive and opportunity: rival researcher Dr. Michael Torres, research assistant Kevin Park, and graduate student Lisa Wang.',
            'suspects' => [
                'dr_torres' => 'Dr. Michael Torres',
                'kevin_park' => 'Kevin Park',
                'lisa_wang' => 'Lisa Wang'
            ],
            'evidence' => [
                'keycard_logs' => [
                    'id' => 'keycard_logs',
                    'name' => 'Keycard Access Logs',
                    'description' => 'Electronic records of who accessed the building.',
                    'content' => 'Saturday 11:30 PM: Kevin Park accessed the building. No record of exit until Sunday 6:45 AM. Dr. Torres\' keycard was used Sunday 2:15 AM, but he claims he was at home.',
                    'discovery_text' => 'University security system records.',
                    'relevance' => 'high'
                ],
                'computer_forensics' => [
                    'id' => 'computer_forensics',
                    'name' => 'Computer Forensics',
                    'description' => 'Digital evidence from lab computers.',
                    'content' => 'Research files were copied to an external drive at 3:47 AM Sunday. User login shows Kevin Park\'s credentials, but the IP address traced to Dr. Torres\' home network.',
                    'discovery_text' => 'IT department forensic analysis.',
                    'relevance' => 'high'
                ],
                'motivation_analysis' => [
                    'id' => 'motivation_analysis',
                    'name' => 'Motivation Analysis',
                    'description' => 'Reasons each suspect might want the research.',
                    'content' => 'Kevin Park was recently denied a recommendation letter by Dr. Chen and is bitter about not being included as co-author on papers. He has been vocal about feeling unappreciated.',
                    'discovery_text' => 'Interviews with lab staff and review of email communications.',
                    'relevance' => 'medium'
                ],
                'security_camera' => [
                    'id' => 'security_camera',
                    'name' => 'Security Camera Evidence',
                    'description' => 'Hallway surveillance footage from the weekend.',
                    'content' => 'Camera shows Kevin Park entering the building Saturday night carrying a laptop bag and external hard drive. He appeared nervous and kept looking around.',
                    'discovery_text' => 'Review of hallway security cameras.',
                    'relevance' => 'high'
                ]
            ],
            'hints' => [
                'Check if the digital evidence matches the physical presence of suspects.',
                'Someone with legitimate access might try to frame another person with access.',
                'Look for discrepancies between keycard logs and computer access times.'
            ],
            'solution' => [
                'primary_suspect' => 'kevin_park',
                'motive' => 'Revenge against Dr. Chen for perceived mistreatment and desire to claim credit for research',
                'evidence_summary' => 'Kevin Park used his legitimate access to steal the research data, attempting to frame Dr. Torres by using stolen credentials.',
                'explanation' => 'Kevin Park, feeling unappreciated and vengeful after being denied recognition, stole Dr. Torres\' keycard earlier and used his login credentials remotely to make it appear Dr. Torres was responsible. However, the physical evidence and his presence in the building expose his guilt.'
            ]
        ],
        
        'hotel_scandal' => [
            'id' => 'hotel_scandal',
            'title' => 'The Hotel Scandal',
            'description' => 'Diplomatic secrets leaked from high-security hotel suite. International implications.',
            'difficulty' => DIFFICULTY_EXPERT,
            'category' => CATEGORY_CONSPIRACY,
            'estimated_time' => '70-100 min',
            'points' => 1200,
            'scenario' => 'Classified diplomatic documents discussing a secret trade agreement were leaked to the press from Ambassador Harrison\'s hotel suite during international negotiations. The leak has caused a diplomatic crisis. Three individuals had access to the suite: hotel manager Patricia Stone, diplomatic security agent Robert Kim, and interpreter Maria Santos.',
            'suspects' => [
                'patricia_stone' => 'Patricia Stone (Hotel Manager)',
                'robert_kim' => 'Robert Kim (Security Agent)',
                'maria_santos' => 'Maria Santos (Interpreter)'
            ],
            'evidence' => [
                'communication_records' => [
                    'id' => 'communication_records',
                    'name' => 'Communication Records',
                    'description' => 'Phone and email communications from hotel staff.',
                    'content' => 'Maria Santos made several encrypted calls to an unknown number in the days leading up to the leak. Communications analysis suggests contact with foreign intelligence services.',
                    'discovery_text' => 'NSA surveillance and communication intercepts.',
                    'relevance' => 'high'
                ],
                'financial_forensics' => [
                    'id' => 'financial_forensics',
                    'name' => 'Financial Forensics',
                    'description' => 'Unusual financial transactions by suspects.',
                    'content' => 'Maria Santos received a $50,000 wire transfer from an untraceable offshore account two days after the leak. Money was quickly moved to multiple accounts.',
                    'discovery_text' => 'Financial intelligence investigation.',
                    'relevance' => 'high'
                ],
                'hotel_security' => [
                    'id' => 'hotel_security',
                    'name' => 'Hotel Security Analysis',
                    'description' => 'Investigation of hotel security protocols.',
                    'content' => 'Patricia Stone disabled the usual security sweeps of the diplomatic suite and granted special access to Maria Santos for "cultural preparation" sessions.',
                    'discovery_text' => 'Review of hotel security procedures and authorization logs.',
                    'relevance' => 'medium'
                ],
                'document_forensics' => [
                    'id' => 'document_forensics',
                    'name' => 'Document Forensics',
                    'description' => 'Analysis of how classified documents were accessed.',
                    'content' => 'Documents were photographed with a high-resolution camera during a legitimate interpretation session. Photo metadata indicates images were taken during Maria\'s scheduled meeting with the Ambassador.',
                    'discovery_text' => 'Digital forensics analysis of leaked documents.',
                    'relevance' => 'high'
                ],
                'background_investigation' => [
                    'id' => 'background_investigation',
                    'name' => 'Background Investigation',
                    'description' => 'Deep background checks on all suspects.',
                    'content' => 'Maria Santos has family members living under oppressive conditions in her home country. Foreign intelligence likely exploited this vulnerability to recruit her as an asset.',
                    'discovery_text' => 'CIA background investigation and family situation analysis.',
                    'relevance' => 'medium'
                ]
            ],
            'hints' => [
                'Look for patterns in communication and financial transactions that suggest foreign involvement.',
                'Someone with legitimate access to diplomatic meetings would be an ideal intelligence asset.',
                'Consider who might be vulnerable to coercion due to family situations abroad.'
            ],
            'solution' => [
                'primary_suspect' => 'maria_santos',
                'motive' => 'Coercion by foreign intelligence threatening her family, combined with financial incentives',
                'evidence_summary' => 'Maria Santos was recruited as a foreign intelligence asset and used her position to photograph classified documents during interpretation sessions.',
                'explanation' => 'Maria Santos was compromised by foreign intelligence services who threatened her family while offering substantial financial compensation. She used her legitimate access as an interpreter to photograph classified documents during official meetings, then transmitted them to her handlers through encrypted communications.'
            ]
        ]
    ];
}

// Get specific case data
function getCase($caseId) {
    $cases = getCases();
    return isset($cases[$caseId]) ? $cases[$caseId] : null;
}

// Get cases by difficulty
function getCasesByDifficulty($difficulty) {
    $cases = getCases();
    return array_filter($cases, function($case) use ($difficulty) {
        return $case['difficulty'] === $difficulty;
    });
}

// Get cases by category  
function getCasesByCategory($category) {
    $cases = getCases();
    return array_filter($cases, function($case) use ($category) {
        return $case['category'] === $category;
    });
}

// Initialize user case progress
function initializeCaseProgress($caseId) {
    // Only initialize if no current case or different case
    if (!isset($_SESSION['current_case']) || $_SESSION['current_case']['case_id'] !== $caseId) {
        // Try to load existing progress first
        $existingProgress = null;
        if (isset($_SESSION['username'])) {
            $existingProgress = loadCaseProgressFromFile($_SESSION['username'], $caseId);
        }
        
        if ($existingProgress) {
            // Restore existing progress
            $_SESSION['current_case'] = $existingProgress;
        } else {
            // Create new progress
            $_SESSION['current_case'] = [
                'case_id' => $caseId,
                'start_time' => time(),
                'evidence_collected' => [],
                'hints_used' => 0,
                'current_phase' => 'investigation',
                'score' => 0,
                'cross_reference' => [
                    'selected_evidence' => [],
                    'connections' => [],
                    'analysis_notes' => ''
                ],
                'investigation_notes' => '',
                'last_updated' => time()
            ];
        }
    }
}

// Add evidence to user's collection
function collectEvidence($evidenceId) {
    if (!isset($_SESSION['current_case']['evidence_collected'])) {
        $_SESSION['current_case']['evidence_collected'] = [];
    }
    
    if (!in_array($evidenceId, $_SESSION['current_case']['evidence_collected'])) {
        $_SESSION['current_case']['evidence_collected'][] = $evidenceId;
        updateCaseProgress(); // Auto-save progress
        return true;
    }
    return false;
}

// Check if user has collected specific evidence
function hasEvidence($evidenceId) {
    if (!isset($_SESSION['current_case']['evidence_collected'])) {
        return false;
    }
    
    $evidenceArray = $_SESSION['current_case']['evidence_collected'];
    if (!is_array($evidenceArray)) {
        return false;
    }
    
    return in_array($evidenceId, $evidenceArray);
}

// Cross-reference management functions
function addToCrossReference($evidenceId) {
    if (!isset($_SESSION['current_case']['cross_reference']['selected_evidence'])) {
        $_SESSION['current_case']['cross_reference']['selected_evidence'] = [];
    }
    
    if (!in_array($evidenceId, $_SESSION['current_case']['cross_reference']['selected_evidence'])) {
        $_SESSION['current_case']['cross_reference']['selected_evidence'][] = $evidenceId;
        updateCaseProgress();
        return true;
    }
    return false;
}

function removeFromCrossReference($evidenceId) {
    if (isset($_SESSION['current_case']['cross_reference']['selected_evidence'])) {
        $key = array_search($evidenceId, $_SESSION['current_case']['cross_reference']['selected_evidence']);
        if ($key !== false) {
            unset($_SESSION['current_case']['cross_reference']['selected_evidence'][$key]);
            $_SESSION['current_case']['cross_reference']['selected_evidence'] = array_values($_SESSION['current_case']['cross_reference']['selected_evidence']);
            updateCaseProgress();
            return true;
        }
    }
    return false;
}

function clearCrossReference() {
    $_SESSION['current_case']['cross_reference']['selected_evidence'] = [];
    $_SESSION['current_case']['cross_reference']['connections'] = [];
    updateCaseProgress();
}

function addConnection($evidence1, $evidence2, $connectionType = 'related') {
    if (!isset($_SESSION['current_case']['cross_reference']['connections'])) {
        $_SESSION['current_case']['cross_reference']['connections'] = [];
    }
    
    $connectionId = $evidence1 . '_' . $evidence2;
    $_SESSION['current_case']['cross_reference']['connections'][$connectionId] = [
        'evidence1' => $evidence1,
        'evidence2' => $evidence2,
        'type' => $connectionType,
        'created_at' => time()
    ];
    updateCaseProgress();
}

function updateInvestigationNotes($notes) {
    $_SESSION['current_case']['investigation_notes'] = $notes;
    updateCaseProgress();
}

function updateCaseProgress() {
    $_SESSION['current_case']['last_updated'] = time();
    saveCaseProgressToFile();
}

function saveCaseProgressToFile() {
    if (!isset($_SESSION['username']) || !isset($_SESSION['current_case'])) {
        return false;
    }
    
    $username = $_SESSION['username'];
    $caseProgress = $_SESSION['current_case'];
    
    // Create progress directory if it doesn't exist
    $progressDir = DATA_DIR . 'progress/';
    if (!file_exists($progressDir)) {
        mkdir($progressDir, 0755, true);
    }
    
    // Save progress file for this user
    $progressFile = $progressDir . $username . '.json';
    $allProgress = [];
    
    if (file_exists($progressFile)) {
        $allProgress = readJsonFile($progressFile);
    }
    
    // Ensure required fields are present
    $caseProgress['current_phase'] = 'investigation';
    $caseProgress['last_updated'] = time();
    
    $allProgress[$caseProgress['case_id']] = $caseProgress;
    writeJsonFile($progressFile, $allProgress);
    
    return true;
}

function loadCaseProgressFromFile($username, $caseId) {
    $progressFile = DATA_DIR . 'progress/' . $username . '.json';
    
    if (!file_exists($progressFile)) {
        return null;
    }
    
    $allProgress = readJsonFile($progressFile);
    return isset($allProgress[$caseId]) ? $allProgress[$caseId] : null;
}

function deleteCaseProgress($username, $caseId) {
    $progressFile = DATA_DIR . 'progress/' . $username . '.json';
    
    if (!file_exists($progressFile)) {
        return true; // Nothing to delete
    }
    
    $allProgress = readJsonFile($progressFile);
    
    // Remove the specific case progress
    if (isset($allProgress[$caseId])) {
        unset($allProgress[$caseId]);
        
        // Save the updated progress file
        writeJsonFile($progressFile, $allProgress);
    }
    
    return true;
}

function restoreUserProgress($username) {
    $progressFile = DATA_DIR . 'progress/' . $username . '.json';
    
    if (!file_exists($progressFile)) {
        return false;
    }
    
    $allProgress = readJsonFile($progressFile);
    
    // Find the most recent incomplete case
    $mostRecent = null;
    $mostRecentTime = 0;
    
    foreach ($allProgress as $caseId => $progress) {
        if (isset($progress['current_phase']) && $progress['current_phase'] === 'investigation' && isset($progress['last_updated']) && $progress['last_updated'] > $mostRecentTime) {
            $mostRecent = $progress;
            $mostRecentTime = $progress['last_updated'];
        }
    }
    
    if ($mostRecent) {
        $_SESSION['current_case'] = $mostRecent;
        return true;
    }
    
    return false;
}

// Enhanced scoring system with sophisticated calculations
function calculateAdvancedScore($caseData, $sessionData) {
    $maxScore = $caseData['points']; // Maximum possible score
    $timeTaken = time() - $sessionData['start_time'];
    $hintsUsed = $sessionData['hints_used'] ?? 0;
    $evidenceCollected = count($sessionData['evidence_collected'] ?? []);
    $totalEvidence = count($caseData['evidence']);
    $crossRefEvidence = count($sessionData['cross_reference']['selected_evidence'] ?? []);
    $connections = count($sessionData['cross_reference']['connections'] ?? []);
    
    // Parse estimated time to get target time in minutes
    $estimatedTime = $caseData['estimated_time'];
    preg_match('/(\d+)-(\d+)/', $estimatedTime, $matches);
    $targetMinutes = isset($matches[2]) ? (int)$matches[2] : 60; // Use upper bound or default 60
    
    $minutesTaken = ceil($timeTaken / 60);
    
    // Start with base score
    $currentScore = $maxScore;
    
    // === TIME DECAY ===
    // For every minute over target time, lose 2% of max score
    if ($minutesTaken > $targetMinutes) {
        $overtimeMinutes = $minutesTaken - $targetMinutes;
        $timeDecay = $maxScore * 0.02 * $overtimeMinutes; // 2% per minute over
        $currentScore -= $timeDecay;
    }
    
    // === HINT PENALTY ===
    // Each hint costs 10% of max score
    $hintPenalty = $maxScore * 0.10 * $hintsUsed;
    $currentScore -= $hintPenalty;
    
    // === SPEED BONUS ===
    // If completed in less than 25% of target time, +50 points bonus
    $speedBonus = 0;
    if ($minutesTaken <= ($targetMinutes * 0.25)) {
        $speedBonus = 50;
        $currentScore += $speedBonus;
    }
    
    // === EVIDENCE COLLECTION BONUS ===
    // Bonus for collecting evidence (up to 10% of max score)
    $evidenceBonus = ($evidenceCollected / $totalEvidence) * ($maxScore * 0.10);
    $currentScore += $evidenceBonus;
    
    // === FINAL SCORE CEILING ===
    // Never exceed max score
    $finalScore = min($currentScore, $maxScore);
    
    // Never go below 0
    $finalScore = max(0, $finalScore);
    
    return [
        'total_score' => round($finalScore),
        'breakdown' => [
            'base_score' => $maxScore,
            'time_decay' => $minutesTaken > $targetMinutes ? -round($maxScore * 0.02 * ($minutesTaken - $targetMinutes)) : 0,
            'hint_penalty' => -round($hintPenalty),
            'speed_bonus' => round($speedBonus),
            'evidence_bonus' => round($evidenceBonus),
            'final_capped' => round($finalScore)
        ],
        'stats' => [
            'time_taken' => $timeTaken,
            'time_taken_minutes' => $minutesTaken,
            'target_minutes' => $targetMinutes,
            'evidence_collected' => $evidenceCollected,
            'total_evidence' => $totalEvidence,
            'evidence_percentage' => round(($evidenceCollected / $totalEvidence) * 100),
            'cross_ref_evidence' => $crossRefEvidence,
            'connections_made' => $connections,
            'hints_used' => $hintsUsed,
            'overtime_minutes' => max(0, $minutesTaken - $targetMinutes)
        ]
    ];
}

// Simplified scoring system - all old complex functions removed

// Get current score preview during investigation
function getCurrentScorePreview($caseId) {
    if (!isset($_SESSION['current_case']) || $_SESSION['current_case']['case_id'] !== $caseId) {
        return null;
    }
    
    $case = getCase($caseId);
    if (!$case) {
        return null;
    }
    
    return calculateAdvancedScore($case, $_SESSION['current_case']);
}

// Legacy function for backward compatibility
function calculateScore($caseData, $timeTaken, $hintsUsed, $correctAnswers) {
    $sessionData = [
        'start_time' => time() - $timeTaken,
        'hints_used' => $hintsUsed,
        'evidence_collected' => $_SESSION['current_case']['evidence_collected'] ?? [],
        'cross_reference' => $_SESSION['current_case']['cross_reference'] ?? ['selected_evidence' => [], 'connections' => []]
    ];
    
    $result = calculateAdvancedScore($caseData, $sessionData);
    return $result['total_score'];
}
?>