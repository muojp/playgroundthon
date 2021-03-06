--[[
	TASK_Genericを使用してLuaでタスクを記述する例
]]

tblCHAR2 = {}

countCHAR2 = 0

_BossHP = 530000
_Money  = 0 


function initChar2(key, index, x, y, asset)
	local tbl = {}
	
	-- add execute & die method
	pGenTask = TASK_Generic("execute_char2", "die_char2", key)

	-- set Char status
	tbl.x = x
	tbl.y = y
	tbl.atk   = index ^ 2
	tbl.speed = 1 - (index * 0.1)
	tbl.asset = asset	--filename
	
	-- CAN PASS pGenTask as parent, but pGenTask has not graphic node : nodes are attached to ROOT.
	tbl.image = UI_SimpleItem(pGenTask, 6000, x, y, asset)

	tblCHAR2[key] = tbl
	
	countCHAR2 = countCHAR2 + 1
end

function execute_char2(pTask, deltaT, key)
	prop = TASK_getProperty(tblCHAR2[key].image)
	prop.x = prop.x + tblCHAR2[key].speed
	TASK_setProperty(tblCHAR2[key].image, prop)
	if prop.x > 768 then
		
		tblBOSS1["boss1"].status = 1
		_BossHP = _BossHP - tblCHAR2[key].atk
		
		-- Kill generic task in this sample
		TASK_kill(pTask)
		
		-- If we did not pass pGenTask as parent,
		-- we need to kill here, can NOT do it in die_char2.
		-- => Need to kill in execute.
		TASK_kill(tblCHAR2[key].image)
		countCHAR2 = countCHAR2 - 1
	end
end

function die_char2(pTask, key)
	-- NEVER kill in DIE : forbidden by engine, you will get an assert.
	--TASK_kill(tblCHAR2[key].image)
	tblCHAR2[key] = nil
	syslog(string.format("kill char2[%s]", key))
end
