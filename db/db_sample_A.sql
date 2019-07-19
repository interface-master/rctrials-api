USE `rctrials`;

SELECT 'test-test-test' INTO @testuser;
SELECT 'ab12' INTO @trialID;

-- create test user
INSERT INTO `users`
(`id`, `email`, `name`, `role`, `valid`)
VALUES
(@testuser, 'test@test.com', 'Test', 'admin', true);

-- populate a new trial with surveys and questions
INSERT INTO `trials`
(`uid`, `title`, `regopen`, `regclose`, `trialstart`, `trialend`, `trialtype`, `timezone`, `created`, `updated`)
VALUES
(@testuser, 'Test Trial', '2019-01-01', '2119-01-01', '2019-01-01', '2119-01-01', 'simple', 'America/Toronto', NOW(), NOW());

UPDATE `trials` SET `tid` = @trialID WHERE `uid` = @testuser;

-- populate groups
INSERT INTO `groups`
(`tid`, `gid`, `name`, `size`, `size_n`)
VALUES
(@trialID, 0, 'Control', 'auto', 0),
(@trialID, 1, 'Experiment', 'auto', 0);

-- populate surveys
INSERT INTO `surveys`
(`tid`, `sid`, `name`, `groups`, `pre`, `during`, `post`, `interval`, `frequency`)
VALUES
(@trialID, 0, 'Demographics', '[0,1]', 1, 0, 0, 1, 'days'),
(@trialID, 1, 'PHQ-4', '[0,1]', 0, 1, 0, 7, 'days');

-- populate questions
INSERT INTO `questions`
(`tid`, `sid`, `qid`, `text`, `type`, `options`)
VALUES
(@trialID, 0, 0, "What is your age?", "slider", "Under 18 | 18-24 years old | 25-34 years old | 35-44 years old | 45-54 years old | Over 55"),
(@trialID, 0, 2, "With which gender do you most identify?", "radio", "Prefer not to say | Female | Male | Other"),
(@trialID, 0, 3, "What is your ethnicity?", "radio", "Prefer not to say | White | Hispanic or Latino | Black or African American | Native | Asian / Pacific Islander | Other"),
(@trialID, 0, 4, "What is the highest degree or level of school you have completed?", "radio", "Less than a high school diploma | High school degree or equivalent | Bachelor's degree (e.g. BA, BS) | Master's degree (e.g. MA, MS, MEd) | Doctorate (e.g. PhD, EdD) | Other"),
(@trialID, 0, 5, "What is your current employment status?", "radio", "Employed full-time (40+ hours a week) | Employed part-time (less than 40 hours a week) | Unemployed (currently looking for work) | Unemployed (not currently looking for work) | Student | Retired | Self-employed | Unable to work"),
(@trialID, 0, 6, "Where do you reside?", "text", ""),
(@trialID, 0, 7, "What is your marital status?", "radio", "Single (never married) | Married | In a domestic partnership | Divorced | Widowed"),
(@trialID, 0, 8, "What is your household income?", "radio", "Below $10k | $10 - $50k | $50k - $100k | $100k - $150k | Over $150k"),
(@trialID, 1, 1, "Feeling nervous, anxious, or on edge", "likert", "Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]"),
(@trialID, 1, 11, "Not being able to stop or control worrying", "likert", "Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]"),
(@trialID, 1, 12, "Little interest or pleasure in doing things", "likert", "Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]"),
(@trialID, 1, 13, "Feeling down, depressed, or hopeless", "likert", "Not at all [0] | Several days [1] | More than half the days [2] | Nearly every day [3]");
