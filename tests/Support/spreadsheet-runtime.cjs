const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');

function createRuntime(day = '2026-10-31', withCharts = true) {
    class Range {
        constructor(sheet, row, col, height = 1, width = 1) { Object.assign(this, {sheet, row, col, height, width}); }
        cells(fn) { for (let r = this.row; r < this.row + this.height; r++) for (let c = this.col; c < this.col + this.width; c++) fn(this.sheet.cell(r,c),r,c); return this; }
        setValue(value) {
            const merged=this.sheet.merges.some(m=>m.join(',')===[this.row,this.col,this.height,this.width].join(','));
            return this.cells((cell,r,c)=>{cell.value=merged && (r!==this.row || c!==this.col) ? '' : value;});
        }
        setValues(values) {
            if (values.length !== this.height || values.some(row => row.length !== this.width)) throw new Error('Dimensi setValues tidak cocok');
            return this.cells((cell,r,c) => { cell.value = values[r-this.row][c-this.col]; });
        }
        getValues() { return Array.from({length:this.height},(_,r) => Array.from({length:this.width},(_,c) => this.sheet.cell(this.row+r,this.col+c).value ?? '')); }
        setFormula(value) { return this.setValue(value); }
        setFormulas(values) { return this.setValues(values); }
        clearContent() { return this.cells(cell => {delete cell.value;}); }
        merge() {
            const item = [this.row, this.col, this.height, this.width];
            for (const old of this.sheet.merges) {
                const intersects = old[0] < this.row + this.height && old[0]+old[2] > this.row && old[1] < this.col+this.width && old[1]+old[3] > this.col;
                if (intersects && old.join(',') !== item.join(',')) throw new Error('Sel gabungan bertabrakan: '+JSON.stringify([old,item]));
            }
            if (!this.sheet.merges.some(m => m.join(',') === item.join(','))) this.sheet.merges.push(item);
            return this;
        }
        breakApart() { this.sheet.merges = this.sheet.merges.filter(m => !(m[0] < this.row+this.height && m[0]+m[2] > this.row && m[1] < this.col+this.width && m[1]+m[3] > this.col)); return this; }
        getBandings() { return []; }
        getSheet() { return this.sheet; }
        applyRowBanding() { return {setFirstRowColor(){return this;},setSecondRowColor(){return this;}}; }
    }
    for (const method of ['setFontFamily','setFontSize','setFontWeight','setFontColor','setBackground','setHorizontalAlignment','setVerticalAlignment','setWrap','setNumberFormat']) {
        Range.prototype[method] = function(value) {return this.cells(cell => {cell[method] = value;});};
    }
    Range.prototype.setBorder = function() {return this;};
    const column = letters => [...letters].reduce((n,c) => n*26+c.charCodeAt(0)-64,0);
    class Sheet {
        constructor(name, book) { Object.assign(this,{name,book,grid:{},merges:[],widths:{},heights:{},maxRows:100,maxCols:26,charts:[]}); }
        cell(r,c) {return this.grid[`${r},${c}`] ||= {};}
        getRange(row,col,height=1,width=1) {
            if (typeof row === 'string') {
                const match = /^([A-Z]+)(\d+)(?::([A-Z]+)(\d+))?$/.exec(row);
                if (!match) throw new Error('Rentang tidak dikenal '+row);
                const r=+match[2], c=column(match[1]);
                return new Range(this,r,c,match[4] ? +match[4]-r+1 : 1,match[3] ? column(match[3])-c+1 : 1);
            }
            return new Range(this,row,col,height,width);
        }
        getName(){return this.name;}
        getParent(){return this.book;}
        getLastRow(){return Math.max(3,...Object.entries(this.grid).filter(([,v])=>v.value!==undefined && v.value!=='').map(([k])=>+k.split(',')[0]));}
        getLastColumn(){return Math.max(1,...Object.entries(this.grid).filter(([,v])=>v.value!==undefined && v.value!=='').map(([k])=>+k.split(',')[1]));}
        getMaxRows(){return this.maxRows;}
        getMaxColumns(){return this.maxCols;}
        insertColumnsAfter(_,n){this.maxCols+=n;return this;}
        setColumnWidth(c,v){this.widths[c]=v;return this;}
        setColumnWidths(c,n,v){for(let i=0;i<n;i++)this.widths[c+i]=v;return this;}
        setRowHeight(r,v){this.heights[r]=v;return this;}
        setRowHeights(r,n,v){for(let i=0;i<n;i++)this.heights[r+i]=v;return this;}
        setFrozenRows(){return this;}
        hideColumns(){return this;}
        autoResizeColumn(){return this;}
        clear(){this.grid={};return this;}
        appendRow(row){this.getRange(this.getLastRow()+1,1,1,row.length).setValues([row]);return this;}
        deleteRow(row){const next={};for(const [key,cell]of Object.entries(this.grid)){const [r,c]=key.split(',').map(Number);if(r!==row)next[`${r>row?r-1:r},${c}`]=cell;}this.grid=next;return this;}
        getCharts(){return this.charts;}
        updateChart(chart){const i=this.charts.findIndex(c=>c.id===chart.id);if(i<0)throw new Error('Grafik harus sudah ada');this.charts[i]=chart;return this;}
    }
    function chart(id,col) {
        return {id,col,row:35,ranges:['lama'],getContainerInfo(){return {getAnchorColumn:()=>this.col};},modify(){
            const copy=chart(this.id,this.col);copy.ranges=[...this.ranges];
            return {clearRanges(){copy.ranges=[];return this;},addRange(range){copy.ranges.push([range.sheet.name,range.row,range.col,range.height,range.width]);return this;},
                setNumHeaders(){return this;},setPosition(row,col){copy.row=row;copy.col=col;return this;},setOption(){return this;},build(){return copy;}};
        }};
    }
    const book={sheets:{},getSheetByName(name){return this.sheets[name]||null;},insertSheet(name){return this.sheets[name]=new Sheet(name,this);},getSheets(){return Object.values(this.sheets);},getUrl(){return 'https://example.invalid/test-sheet';},getName(){return 'Uji';}};
    for(const name of ['Dashboard','Data Order','Rincian Biaya HPP','Arus Kas','Beban Konveksi','Beban Pribadi','Pinjaman','Aset'])book.insertSheet(name);
    if(withCharts)book.sheets.Dashboard.charts=[chart(101,1),chart(102,7)];
    const fixed=Date.parse(day+'T12:00:00+07:00');
    class FixedDate extends Date {constructor(...args){super(...(args.length?args:[fixed]));}}
    const menu={addItem(){return this;},addToUi(){return this;}};
    const ctx={Date:FixedDate,Utilities:{formatDate(value){const parts=new Intl.DateTimeFormat('en',{timeZone:'Asia/Jakarta',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(value);const part=type=>parts.find(p=>p.type===type).value;return part('year')+'-'+part('month')+'-'+part('day');}},SpreadsheetApp:{getActiveSpreadsheet:()=>book,getUi:()=>({createMenu:()=>menu}),BorderStyle:{SOLID_THICK:'solid',DASHED:'dashed'},BandingTheme:{LIGHT_GREY:'grey'}},
        LockService:{getScriptLock:()=>({tryLock:()=>true,releaseLock(){}})},
        ContentService:{MimeType:{JSON:'json'},createTextOutput:content=>({content,setMimeType(){return this;}})}};
    vm.createContext(ctx);
    vm.runInContext(fs.readFileSync(path.resolve(__dirname,'../../resources/views/admin/spreadsheet/code.js'),'utf8'),ctx);
    ctx.setupDashboardSheet(book.sheets.Dashboard,null);
    function post(payload){const response=JSON.parse(ctx.doPost({postData:{contents:JSON.stringify(payload)}}).content);if(response.status!=='success')throw new Error(response.message);return response;}
    return {book,ctx,post};
}

if (require.main === module) {
    const input=JSON.parse(fs.readFileSync(0,'utf8'));
    const runtime=createRuntime(input.day,input.withCharts!==false);
    runtime.post({action:'sync_all',...input.payload});
    for(const operation of input.operations||[])runtime.post(operation);
    if(input.rebuild)runtime.ctx.setupDashboardSheet(runtime.book.sheets.Dashboard,null);
    const result=runtime.ctx.refreshDashboard(runtime.book,input.day);
    result.charts=runtime.book.sheets.Dashboard.charts.map(c=>({id:c.id,row:c.row,col:c.col,ranges:c.ranges}));
    result.order_rows=runtime.ctx.dashboardRows(runtime.book,'Data Order',22);
    result.sheet={...runtime.book.sheets.Dashboard};
    delete result.sheet.book;
    delete result.sheet.charts;
    process.stdout.write(JSON.stringify(result));
}
module.exports={createRuntime};
